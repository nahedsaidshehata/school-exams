<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;

class ExamQuestionApiController extends Controller
{
    public function index($exam_id)
    {
        $exam = Exam::with([
            'questions' => function ($query) {
                $query->select('questions.id', 'questions.type', 'questions.difficulty', 'questions.prompt_ar', 'questions.prompt_en', 'questions.metadata')
                    ->orderBy('exam_questions.order_index');
            },
            'questions.options'
        ])->findOrFail($exam_id);

        $questions = $exam->questions->map(function ($question) {
            $data = [
                'id' => $question->id,
                'type' => $question->type,
                'difficulty' => $question->difficulty,
                'prompt_ar' => $question->prompt_ar,
                'prompt_en' => $question->prompt_en,
            ];

            if ($question->options && $question->options->count() > 0) {
                $data['options'] = $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'content_ar' => $option->content_ar,
                        'content_en' => $option->content_en,
                    ];
                });
            } elseif (isset($question->metadata['options'])) {
                $data['options'] = collect($question->metadata['options'])->map(function ($option) {
                    $textAr = $option['text_ar'] ?? ($option['text'] ?? '');
                    $textEn = $option['text_en'] ?? ($option['text'] ?? '');

                    return [
                        'id' => $option['id'] ?? null,
                        'content_ar' => $textAr,
                        'content_en' => $textEn,
                    ];
                })->values();
            } else {
                $data['options'] = [];
            }

            return $data;
        });

        return response()->json(['questions' => $questions], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
