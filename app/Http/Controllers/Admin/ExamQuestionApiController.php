<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExamQuestionApiController extends Controller
{
    public function store(Request $request, $exam_id)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|uuid|exists:lessons,id',
            'type' => 'required|string|in:MCQ,TF,ESSAY',
            'difficulty' => 'required|string|in:EASY,MEDIUM,HARD',
            'prompt_ar' => 'required|string',
            'prompt_en' => 'required|string',
            'points' => 'required|integer|min:1',
            'options' => 'required_if:type,MCQ,TF|array',
            'options.*.text' => 'required_with:options|string',
            'options.*.text_ar' => 'required_with:options|string',
            'correct_option_index' => 'required_if:type,MCQ,TF|integer|min:0',
        ]);

        $exam = Exam::findOrFail($exam_id);

        $metadata = [];
        if (in_array($validated['type'], ['MCQ', 'TF'])) {
            $metadata['options'] = $validated['options'];
            $metadata['correct_option_index'] = $validated['correct_option_index'];
            $metadata['points'] = $validated['points'];
        }

        $question = Question::create([
            'lesson_id' => $validated['lesson_id'],
            'type' => $validated['type'],
            'difficulty' => $validated['difficulty'],
            'prompt_ar' => $validated['prompt_ar'],
            'prompt_en' => $validated['prompt_en'],
            'metadata' => $metadata,
        ]);

        $maxOrderIndex = $exam->questions()->max('exam_questions.order_index') ?? 0;

        $exam->questions()->attach($question->id, [
            'id' => Str::uuid()->toString(),
            'points' => $validated['points'],
            'order_index' => $maxOrderIndex + 1,
        ]);

        return response()->json([
            'message' => 'Question created and attached to exam',
            'question_id' => $question->id,
        ], 201);
    }
}
