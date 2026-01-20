<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use Illuminate\Http\Request;

class AttemptGradingController extends Controller
{
    /**
     * Grade essay questions
     *
     * PATCH /admin/attempts/{attempt}/grade-essay
     */
    public function gradeEssay(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.question_id' => 'required|uuid',
            'grades.*.points_awarded' => 'required|numeric|min:0',
        ]);

        try {
            // Validate attempt status
            if ($attempt->status !== 'PENDING_MANUAL') {
                return response()->json([
                    'error' => 'Attempt is not pending manual grading'
                ], 422);
            }

            // Update essay grades
            foreach ($request->grades as $grade) {
                $answer = AttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $grade['question_id'])
                    ->where('reset_version', $attempt->reset_version)
                    ->first();

                if (!$answer) {
                    return response()->json([
                        'error' => 'Answer not found for question: ' . $grade['question_id']
                    ], 404);
                }

                // Validate points don't exceed max
                $examQuestion = $attempt->exam->examQuestions()
                    ->where('question_id', $grade['question_id'])
                    ->first();

                if ($examQuestion && $grade['points_awarded'] > $examQuestion->points) {
                    return response()->json([
                        'error' => 'Points awarded exceed maximum for question: ' . $grade['question_id']
                    ], 422);
                }

                $answer->update(['points_awarded' => $grade['points_awarded']]);
            }

            // Calculate totals
            $rawScore = $attempt->answers()
                ->where('reset_version', $attempt->reset_version)
                ->sum('points_awarded');

            $percentage = ($attempt->max_possible_score > 0)
                ? ($rawScore / $attempt->max_possible_score) * 100
                : 0;

            $attempt->update([
                'status' => 'GRADED',
                'raw_score' => $rawScore,
                'percentage' => $percentage,
            ]);

            return response()->json([
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'raw_score' => $attempt->raw_score,
                'percentage' => $attempt->percentage,
                'max_possible_score' => $attempt->max_possible_score,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize grading and calculate final scores
     *
     * POST /admin/attempts/{attempt}/finalize-grading
     */
    public function finalizeGrading(Request $request, ExamAttempt $attempt)
    {
        try {
            // Validate attempt status
            if (!in_array($attempt->status, ['SUBMITTED', 'PENDING_MANUAL'])) {
                return response()->json([
                    'error' => 'Attempt must be SUBMITTED or PENDING_MANUAL to finalize grading'
                ], 422);
            }

            // Check if all essay questions have been graded
            $ungradedEssays = $attempt->answers()
                ->where('reset_version', $attempt->reset_version)
                ->whereHas('question', function ($query) {
                    $query->where('type', 'ESSAY');
                })
                ->whereNull('points_awarded')
                ->count();

            if ($ungradedEssays > 0) {
                return response()->json([
                    'error' => "Cannot finalize: {$ungradedEssays} essay question(s) still need grading"
                ], 422);
            }

            // Calculate final scores
            $rawScore = $attempt->answers()
                ->where('reset_version', $attempt->reset_version)
                ->sum('points_awarded');

            $percentage = $attempt->max_possible_score > 0
                ? ($rawScore / $attempt->max_possible_score) * 100
                : 0;

            // Update attempt
            $attempt->update([
                'status' => 'GRADED',
                'raw_score' => $rawScore,
                'percentage' => $percentage,
            ]);

            return response()->json([
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'raw_score' => $attempt->raw_score,
                'percentage' => $attempt->percentage,
                'max_possible_score' => $attempt->max_possible_score,
                'message' => 'Grading finalized successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View attempt details for grading (HTML) + also supports JSON for API/AJAX
     *
     * GET /admin/attempts/{attempt}
     */
    public function show(Request $request, ExamAttempt $attempt)
    {
        $attempt->load([
            'student',
            'exam',
            'exam.examQuestions', // for max points per question
            'answers.question',
            'answers.question.options',
        ]);

        $examQuestionsByQid = $attempt->exam && $attempt->exam->relationLoaded('examQuestions')
            ? $attempt->exam->examQuestions->keyBy('question_id')
            : collect();

        $answers = $attempt->answers
            ->where('reset_version', $attempt->reset_version)
            ->values()
            ->map(function ($answer) use ($examQuestionsByQid) {
                $maxPoints = optional($examQuestionsByQid->get($answer->question_id))->points;

                return [
                    'question_id' => $answer->question_id,
                    'question_type' => $answer->question->type,
                    'question_prompt_en' => $answer->question->prompt_en,
                    'question_prompt_ar' => $answer->question->prompt_ar,
                    'student_response' => $answer->student_response,
                    'points_awarded' => $answer->points_awarded,
                    'max_points' => $maxPoints,
                    'options' => $answer->question->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'content_en' => $option->content_en,
                            'content_ar' => $option->content_ar,
                            'is_correct' => $option->is_correct,
                        ];
                    })->values(),
                ];
            });

        $payload = [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'status' => $attempt->status,
                'reset_version' => $attempt->reset_version,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'max_possible_score' => $attempt->max_possible_score,
                'raw_score' => $attempt->raw_score,
                'percentage' => $attempt->percentage,
                'student' => [
                    'id' => $attempt->student->id,
                    'full_name' => $attempt->student->full_name,
                    'username' => $attempt->student->username,
                ],
                'exam' => [
                    'id' => $attempt->exam->id,
                    'title_en' => $attempt->exam->title_en,
                    'title_ar' => $attempt->exam->title_ar,
                ],
                'answers' => $answers,
            ],
        ];

        // ✅ Keep API compatibility
        if ($request->expectsJson()) {
            return response()->json($payload, 200);
        }

        // ✅ Admin UI page
        return view('admin.attempts.show', [
            'attemptData' => $payload['attempt'],
        ]);
    }
}
