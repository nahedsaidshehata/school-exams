<?php

namespace App\Services;

use App\Models\Lesson;

class LessonAiContextBuilder
{
    public function build(Lesson $lesson, string $langMode = 'both'): array
    {
        $langMode = in_array($langMode, ['ar','en','both']) ? $langMode : 'both';

        // 1) manual content
        $contentAr = trim((string) ($lesson->content_ar ?? ''));
        $contentEn = trim((string) ($lesson->content_en ?? ''));

        // 2) extracted text from attachments (SUCCESS only)
        $attachments = $lesson->attachments ?? collect();
        $extractedTexts = $attachments
            ->where('extraction_status', 'SUCCESS')
            ->pluck('extracted_text')
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->map(fn ($t) => trim($t))
            ->values()
            ->all();

        $combinedExtracted = trim(implode("\n\n---\n\n", $extractedTexts));

        // 3) outcomes (selected for lesson)
        $outcomes = ($lesson->learningOutcomes ?? collect())->map(function ($o) {
            return [
                'id' => (string) $o->id,
                'code' => $o->code ?? null,
                'title_ar' => $o->title_ar ?? null,
                'title_en' => $o->title_en ?? null,
            ];
        })->values()->all();

        // language selection
        $final = [
            'lesson' => [
                'id' => (string) $lesson->id,
                'title_ar' => $lesson->title_ar,
                'title_en' => $lesson->title_en,
            ],
            'lang_mode' => $langMode,
            'manual_content' => [
                'ar' => $contentAr,
                'en' => $contentEn,
            ],
            'attachments_extracted_text' => $combinedExtracted,
            'learning_outcomes' => $outcomes,
        ];

        // Optional: strip content not needed based on lang_mode (keep both for metadata if you want)
        if ($langMode === 'ar') {
            $final['manual_content']['en'] = '';
        } elseif ($langMode === 'en') {
            $final['manual_content']['ar'] = '';
        }

        return $final;
    }
}
