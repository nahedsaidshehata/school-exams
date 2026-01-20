<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamStateResolver;

class ExamQuestionController extends Controller
{
    protected $stateResolver;

    public function __construct(ExamStateResolver $stateResolver)
    {
        $this->stateResolver = $stateResolver;
    }

    /**
     * Get exam questions as JSON (NO answers, NO points)
     * 
     * GET /student/exams/{exam}/questions
     */
    public function index(Exam $exam)
    {
        $student = auth()->user();
        $schoolId = $student->school_id;

        // SECURITY: Verify this exam is assigned to this student
        $isAssigned = $this->stateResolver->isAssignedToStudent($exam, $student);

        if (!$isAssigned) {
            return response()->json([
                'error' => 'This exam is not assigned to you.'
            ], 403);
        }

        // SECURITY: Verify exam is in AVAILABLE state
        $state = $this->stateResolver->resolveState($exam, $student);
        
        if ($state !== 'AVAILABLE') {
            return response()->json([
                'error' => 'Exam is not available. Current state: ' . $state
            ], 423);
        }

        // Load exam with questions (NO correct answers, NO points)
        // SECURITY: Explicitly select only safe columns to prevent data leakage
        $exam->load([
            'examQuestions' => function ($query) {
                // CRITICAL: Select only safe columns from exam_questions pivot table
                // DO NOT include 'points' column
                $query->select('exam_questions.id', 'exam_questions.exam_id', 'exam_questions.question_id', 'exam_questions.order_index')
                    ->orderBy('exam_questions.order_index');
            },
            'examQuestions.question' => function ($query) {
                $query->select('id', 'type', 'difficulty', 'prompt_en', 'prompt_ar');
            },
            'examQuestions.question.options' => function ($query) {
                // CRITICAL: Do NOT include is_correct field
                $query->select('id', 'question_id', 'content_en', 'content_ar', 'order_index')
                    ->orderBy('order_index');
            }
        ]);

        // Format response
        $questions = $exam->examQuestions->map(function ($examQuestion) {
            $question = $examQuestion->question;
            
            $questionData = [
                'id' => $question->id,
                'type' => $question->type,
                'text' => $question->prompt_en, // Primary text field
                'text_ar' => $question->prompt_ar, // Arabic text
                'order_index' => $examQuestion->order_index,
            ];

            // Add options for MCQ and TF questions
            if (in_array($question->type, ['MCQ', 'TF'])) {
                $questionData['options'] = $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'text' => $option->content_en,
                        'text_ar' => $option->content_ar,
                        'order_index' => $option->order_index,
                    ];
                })->values();
            } else {
                // Essay questions have no options
                $questionData['options'] = [];
            }

            return $questionData;
        })->values();

        return response()->json([
            'exam_id' => $exam->id,
            'questions' => $questions,
        ], 200);
    }
}
