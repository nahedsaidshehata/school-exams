# SPRINT 3 PHASE 2 - REMAINING CONTROLLER FILES

This document contains the full content of the remaining controller files and routes that need to be created.

---

## FILE: app/Http/Controllers/Student/AttemptController.php

```php
<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\AttemptService;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    protected $attemptService;

    public function __construct(AttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }

    /**
     * Start a new exam attempt
     * 
     * POST /student/exams/{exam}/start
     */
    public function start(Exam $exam)
    {
        try {
            $student = auth()->user();
            $result = $this->attemptService->startAttempt($student, $exam);
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [403, 409, 423, 429])) {
                $statusCode = 500;
            }
            
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Send heartbeat to keep session alive
     * 
     * POST /student/attempts/{attempt}/heartbeat
     */
    public function heartbeat(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);

        try {
            $student = auth()->user();
            $result = $this->attemptService->heartbeat(
                $attempt,
                $request->session_token,
                $student
            );
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [403, 409, 423])) {
                $statusCode = 500;
            }
            
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Autosave answer
     * 
     * PATCH /student/attempts/{attempt}/save
     */
    public function save(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'session_token' => 'required|string',
            'question_id' => 'required|uuid',
            'reset_version' => 'required|integer',
            'student_response' => 'required|array',
            'focus_log' => 'nullable|array',
        ]);

        try {
            $student = auth()->user();
            $result = $this->attemptService->autosave(
                $attempt,
                $request->question_id,
                $request->student_response,
                $request->reset_version,
                $request->session_token,
                $student,
                $request->focus_log
            );
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [403, 409, 422, 423])) {
                $statusCode = 500;
            }
            
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Reset all answers
     * 
     * POST /student/attempts/{attempt}/reset
     */
    public function reset(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);

        try {
            $student = auth()->user();
            $result = $this->attemptService->resetAll(
                $attempt,
                $request->session_token,
                $student
            );
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [403, 409, 423])) {
                $statusCode = 500;
            }
            
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * Submit attempt for grading
     * 
     * POST /student/attempts/{attempt}/submit
     */
    public function submit(Request $request, ExamAttempt $attempt)
    {
        $request->validate([
            'session_token' => 'required|string',
        ]);

        try {
            $student = auth()->user();
            $result = $this->attemptService->submit(
                $attempt,
                $request->session_token,
                $student
            );
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            if (!in_array($statusCode, [403, 409, 422, 423])) {
                $statusCode = 500;
            }
            
            return response()->json([
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }
}
```

---

## FILE: app/Http/Controllers/Admin/AttemptGradingController.php

```php
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

                if ($grade['points_awarded'] > $examQuestion->points) {
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

            $percentage = ($rawScore / $attempt->max_possible_score) * 100;

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
     * View attempt details for grading
     * 
     * GET /admin/attempts/{attempt}
     */
    public function show(ExamAttempt $attempt)
    {
        $attempt->load([
            'student',
            'exam',
            'answers.question',
            'answers.question.options'
        ]);

        return response()->json([
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'status' => $attempt->status,
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
                'answers' => $attempt->answers->where('reset_version', $attempt->reset_version)->map(function ($answer) {
                    return [
                        'question_id' => $answer->question_id,
                        'question_type' => $answer->question->type,
                        'question_prompt_en' => $answer->question->prompt_en,
                        'question_prompt_ar' => $answer->question->prompt_ar,
                        'student_response' => $answer->student_response,
                        'points_awarded' => $answer->points_awarded,
                        'options' => $answer->question->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'content_en' => $option->content_en,
                                'content_ar' => $option->content_ar,
                                'is_correct' => $option->is_correct,
                            ];
                        }),
                    ];
                }),
            ],
        ], 200);
    }
}
```

---

## ROUTES TO ADD TO routes/web.php

Add these routes to the existing `routes/web.php` file:

```php
// Student attempt routes
Route::prefix('student')->middleware(['auth', 'role:student', 'tenant'])->name('student.')->group(function () {
    // ... existing student routes ...
    
    // Exam attempts
    Route::post('/exams/{exam}/start', [\App\Http\Controllers\Student\AttemptController::class, 'start'])->name('exams.start');
    Route::post('/attempts/{attempt}/heartbeat', [\App\Http\Controllers\Student\AttemptController::class, 'heartbeat'])->name('attempts.heartbeat');
    Route::patch('/attempts/{attempt}/save', [\App\Http\Controllers\Student\AttemptController::class, 'save'])->name('attempts.save');
    Route::post('/attempts/{attempt}/reset', [\App\Http\Controllers\Student\AttemptController::class, 'reset'])->name('attempts.reset');
    Route::post('/attempts/{attempt}/submit', [\App\Http\Controllers\Student\AttemptController::class, 'submit'])->name('attempts.submit');
});

// Admin attempt grading routes
Route::prefix('admin')->middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    // ... existing admin routes ...
    
    // Attempt grading
    Route::get('/attempts/{attempt}', [\App\Http\Controllers\Admin\AttemptGradingController::class, 'show'])->name('attempts.show');
    Route::patch('/attempts/{attempt}/grade-essay', [\App\Http\Controllers\Admin\AttemptGradingController::class, 'gradeEssay'])->name('attempts.grade-essay');
});
