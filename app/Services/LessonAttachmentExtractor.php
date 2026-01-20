<?php

namespace App\Services;

use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentExtractor
{
    /**
     * Returns extracted text string.
     * Throws exception on failure.
     */
    public function extract(LessonAttachment $attachment): string
    {
        $path = $attachment->path;
        $disk = $attachment->disk ?: 'public';

        if (!$path || !Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('File not found on disk.');
        }

        $tmp = $this->copyToTemp($disk, $path);
        $ext = strtolower($attachment->extension ?: pathinfo($path, PATHINFO_EXTENSION));

        try {
            if (in_array($ext, ['docx'])) {
                return $this->extractDocx($tmp);
            }

            if (in_array($ext, ['pdf'])) {
                return $this->extractPdf($tmp);
            }

            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                return $this->extractImageOcr($tmp);
            }

            throw new \RuntimeException('Unsupported file type for extraction: ' . ($ext ?: 'unknown'));
        } finally {
            @unlink($tmp);
        }
    }

    private function copyToTemp(string $disk, string $path): string
    {
        $contents = Storage::disk($disk)->get($path);
        $tmp = tempnam(sys_get_temp_dir(), 'lesson_att_');
        file_put_contents($tmp, $contents);
        return $tmp;
    }

    private function extractDocx(string $tmpFile): string
    {
        $zip = new \ZipArchive();
        $ok = $zip->open($tmpFile);
        if ($ok !== true) {
            throw new \RuntimeException('Failed to open DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            throw new \RuntimeException('DOCX document.xml not found.');
        }

        // Very simple XML text extraction
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $text = preg_replace("/\s+/u", " ", trim($text));

        if (mb_strlen($text) < 5) {
            throw new \RuntimeException('DOCX extraction produced empty text.');
        }

        return $text;
    }

    private function extractPdf(string $tmpFile): string
    {
        // Requires pdftotext installed and available in PATH
        $cmd = $this->findCommand('pdftotext');
        if (!$cmd) {
            throw new \RuntimeException('Missing dependency: pdftotext (install Poppler).');
        }

        $out = tempnam(sys_get_temp_dir(), 'pdf_txt_');

        // pdftotext "in.pdf" "out.txt"
        $command = "\"{$cmd}\" \"" . $tmpFile . "\" \"" . $out . "\"";
        $this->run($command);

        $text = @file_get_contents($out) ?: '';
        @unlink($out);

        $text = preg_replace("/\s+/u", " ", trim($text));
        if (mb_strlen($text) < 5) {
            throw new \RuntimeException('PDF extraction produced empty text.');
        }

        return $text;
    }

    private function extractImageOcr(string $tmpFile): string
    {
        // Requires tesseract installed and available in PATH
        $cmd = $this->findCommand('tesseract');
        if (!$cmd) {
            throw new \RuntimeException('Missing dependency: tesseract OCR.');
        }

        $outBase = tempnam(sys_get_temp_dir(), 'ocr_');
        @unlink($outBase); // tesseract expects base name

        // tesseract image outbase -l eng+ara
        $command = "\"{$cmd}\" \"" . $tmpFile . "\" \"" . $outBase . "\" -l eng+ara";
        $this->run($command);

        $txtFile = $outBase . '.txt';
        $text = @file_get_contents($txtFile) ?: '';
        @unlink($txtFile);

        $text = preg_replace("/\s+/u", " ", trim($text));
        if (mb_strlen($text) < 5) {
            throw new \RuntimeException('OCR produced empty text.');
        }

        return $text;
    }

    private function findCommand(string $name): ?string
    {
        // Windows: where, Linux/mac: which
        $finder = stripos(PHP_OS, 'WIN') === 0 ? "where {$name}" : "which {$name}";
        $out = @shell_exec($finder);
        if (!$out) return null;

        $lines = preg_split("/\r\n|\n|\r/", trim($out));
        return $lines[0] ?? null;
    }

    private function run(string $command): void
    {
        $output = [];
        $code = 0;
        @exec($command . ' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Extractor command failed: ' . implode("\n", $output));
        }
    }
}
