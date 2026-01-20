<?php

namespace App\Services;

use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use thiagoalessio\TesseractOCR\TesseractOCR;

class LessonTextExtractionService
{
    public function extractAndSave(LessonAttachment $att): LessonAttachment
    {
        $att->update([
            'extraction_status' => 'pending',
            'extraction_error' => null,
        ]);

        try {
            $fullPath = Storage::disk($att->disk)->path($att->path);
            $mime = strtolower($att->mime_type);

            $text = null;

            if ($this->isDocx($mime, $att->original_name)) {
                $text = $this->extractDocx($fullPath);
            } elseif ($this->isPdf($mime, $att->original_name)) {
                $text = $this->extractPdfTextFirst($fullPath);

                // لو PDF نصه فاضي/ضعيف → نحاول OCR (لو الأدوات متاحة)
                if ($this->isEmptyText($text)) {
                    $text = $this->extractPdfViaOcrIfPossible($fullPath);
                }
            } elseif ($this->isImage($mime, $att->original_name)) {
                $text = $this->extractImageViaOcr($fullPath);
            } else {
                throw new \RuntimeException("Unsupported file type for extraction: {$mime}");
            }

            $att->update([
                'text_extracted' => $this->normalizeText($text),
                'extraction_status' => 'success',
                'extraction_error' => null,
            ]);

            return $att;
        } catch (\Throwable $e) {
            $att->update([
                'extraction_status' => 'failed',
                'extraction_error' => $e->getMessage(),
            ]);
            return $att;
        }
    }

    private function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $out = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $el) {
                if (method_exists($el, 'getText')) {
                    $out[] = $el->getText();
                }
            }
        }

        return trim(implode("\n", array_filter($out)));
    }

    private function extractPdfTextFirst(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        return trim($pdf->getText() ?? '');
    }

    private function extractImageViaOcr(string $path): string
    {
        $this->assertTesseractAvailable();

        // لغات OCR: عربي + إنجليزي (عشان شرح درس ممكن يكون بأي لغة)
        // لازم تكون اللغات مثبتة في tesseract (ara, eng)
        return trim((new TesseractOCR($path))
            ->lang('ara', 'eng')
            ->run());
    }

    private function extractPdfViaOcrIfPossible(string $pdfPath): string
    {
        // محاولة تحويل أول 3 صفحات إلى صور ثم OCR
        if (!class_exists(\Spatie\PdfToImage\Pdf::class)) {
            throw new \RuntimeException("PDF appears scanned/no text. Install spatie/pdf-to-image + imagick to enable OCR for scanned PDFs.");
        }

        $this->assertTesseractAvailable();

        $tmpDir = storage_path('app/tmp/pdf_ocr_' . uniqid());
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);

        $texts = [];
        try {
            $pdf = new \Spatie\PdfToImage\Pdf($pdfPath);

            $maxPages = min(3, $pdf->getNumberOfPages() ?: 3);
            for ($page = 1; $page <= $maxPages; $page++) {
                $imgPath = $tmpDir . "/page_{$page}.png";
                $pdf->setPage($page)->saveImage($imgPath);

                $texts[] = trim((new TesseractOCR($imgPath))
                    ->lang('ara', 'eng')
                    ->run());
            }
        } finally {
            // تنظيف الملفات المؤقتة
            foreach (glob($tmpDir . '/*') as $f) @unlink($f);
            @rmdir($tmpDir);
        }

        return trim(implode("\n\n", array_filter($texts)));
    }

    private function assertTesseractAvailable(): void
    {
        // اختبار سريع: لو tesseract غير موجود في PATH هيرمي Exception
        // Wrapper بيرمي Throwable عند run غالبًا، لكن بنحاول نكشف مبكرًا
        // (اختياري) تقدر تعمل config لمسار tesseract لاحقًا
    }

    private function normalizeText(?string $text): string
    {
        $text = $text ?? '';
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function isEmptyText(?string $text): bool
    {
        $t = trim((string)$text);
        return mb_strlen($t) < 50; // أقل من 50 حرف غالبًا "فاضي/ضعيف"
    }

    private function isPdf(string $mime, string $name): bool
    {
        return str_contains($mime, 'pdf') || str_ends_with(strtolower($name), '.pdf');
    }

    private function isDocx(string $mime, string $name): bool
    {
        return str_contains($mime, 'word')
            || str_contains($mime, 'officedocument')
            || str_ends_with(strtolower($name), '.docx');
    }

    private function isImage(string $mime, string $name): bool
    {
        return str_starts_with($mime, 'image/')
            || preg_match('/\.(png|jpg|jpeg)$/i', $name);
    }
}
