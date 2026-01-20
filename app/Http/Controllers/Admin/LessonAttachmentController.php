<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Services\LessonAttachmentExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonAttachmentController extends Controller
{
    public function store(Request $request, Lesson $lesson, LessonAttachmentExtractor $extractor)
    {
        $maxKb = 102400; // 100MB

        // Support BOTH: files[] (multiple) and file (single)
        if ($request->hasFile('files')) {
            $request->validate([
                'files' => ['required', 'array', 'min:1'],
                'files.*' => ['file', "max:$maxKb", 'mimes:pdf,docx,jpg,jpeg,png,webp'],
            ]);
            $files = $request->file('files') ?? [];
        } else {
            $request->validate([
                'file' => ['required', 'file', "max:$maxKb", 'mimes:pdf,docx,jpg,jpeg,png,webp'],
            ]);
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return back()->withErrors(['files' => 'Please select at least one file.'])->withInput();
        }

        $disk = 'public';
        $uploadedCount = 0;

        foreach ($files as $file) {
            if (!$file) continue;

            $originalName = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            $size = (int) $file->getSize();

            // sha256 (optional)
            $sha256 = null;
            try {
                $sha256 = hash_file('sha256', $file->getRealPath());
            } catch (\Throwable $e) {
                // ignore
            }

            // Create DB row first
            $attachment = LessonAttachment::create([
                'lesson_id' => $lesson->id,
                'uploaded_by' => auth()->id(),
                'original_name' => $originalName,
                'disk' => $disk,
                'path' => 'temp',
                'mime_type' => $mime,
                'extension' => $ext ?: null,
                'size_bytes' => $size,
                'sha256' => $sha256,

                'extraction_status' => 'IDLE',
                'extraction_error' => null,
                'extracted_text' => null,
                'extracted_text_updated_at' => null,
            ]);

            // Store file
            $safeExt = $ext ?: 'bin';
            $dir = "lessons/{$lesson->id}/attachments";
            $filename = "{$attachment->id}.{$safeExt}";
            $path = "{$dir}/{$filename}";

            Storage::disk($disk)->putFileAs($dir, $file, $filename);
            $attachment->update(['path' => $path]);

            $uploadedCount++;

            // ✅ Try extraction immediately (sync)
            // If dependencies missing, mark FAILED with clear error (does not break upload)
            try {
                $attachment->update([
                    'extraction_status' => 'PROCESSING',
                    'extraction_error' => null,
                ]);

                $text = $extractor->extract($attachment);

                $attachment->update([
                    'extraction_status' => 'SUCCESS',
                    'extracted_text' => $text,
                    'extracted_text_updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $attachment->update([
                    'extraction_status' => 'FAILED',
                    'extraction_error' => $e->getMessage(),
                ]);
            }
        }

        $msg = $uploadedCount === 1
            ? 'تم رفع الملف بنجاح ✅'
            : "تم رفع {$uploadedCount} ملفات بنجاح ✅";

        return back()->with('success', $msg);
    }

    public function reextract(Lesson $lesson, LessonAttachment $attachment, LessonAttachmentExtractor $extractor)
    {
        if ($attachment->lesson_id !== $lesson->id) {
            abort(404);
        }

        try {
            $attachment->update([
                'extraction_status' => 'PROCESSING',
                'extraction_error' => null,
            ]);

            $text = $extractor->extract($attachment);

            $attachment->update([
                'extraction_status' => 'SUCCESS',
                'extracted_text' => $text,
                'extracted_text_updated_at' => now(),
            ]);

            return back()->with('success', 'تم إعادة استخراج النص بنجاح ✅');
        } catch (\Throwable $e) {
            $attachment->update([
                'extraction_status' => 'FAILED',
                'extraction_error' => $e->getMessage(),
            ]);

            return back()->withErrors(['extract' => 'فشل استخراج النص: ' . $e->getMessage()]);
        }
    }

    public function destroy(Lesson $lesson, LessonAttachment $attachment)
    {
        if ($attachment->lesson_id !== $lesson->id) {
            abort(404);
        }

        try {
            if ($attachment->disk && $attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $attachment->delete();

        return back()->with('success', 'تم حذف الملف ✅');
    }
}
