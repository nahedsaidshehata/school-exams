<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Models\AnswerTimeLog;
use App\Models\User;
use App\Models\QuestionOption;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttemptService
{
    protected $stateResolver;

    public function __construct(ExamStateResolver $stateResolver)
    {
        $this->stateResolver = $stateResolver;
    }

    /**
     * Start a new exam attempt
     * 
     * @param User $student
     * @param Exam $exam
     * @return array
     * @throws \Exception
     */
    public function startAttempt(User $student, Exam $exam): array
    {
        $schoolId = $student->school_id;

        // 1. Verify assigned
        if (!$this->stateResolver->isAssignedToStudent($exam, $student)) {
            throw new \Exception('This exam is not assigned to you.', 403);
        }

        // 2. Verify state is AVAILABLE
        $state = $this->stateResolver->resolveState($exam, $student);
        if ($state !== 'AVAILABLE') {
            throw new \Exception('Exam is not available. Current state: ' . $state, 423);
        }

        // 3. Check max attempts
        $attemptCount = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $schoolId)
            ->count();

        if ($attemptCount >= $exam->max_attempts) {
            throw new \Exception('Maximum attempts exceeded.', 429);
        }

        // 4. Check for existing IN_PROGRESS attempt
        $existingAttempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $schoolId)
            ->where('status', 'IN_PROGRESS')
            ->first();

        if ($existingAttempt) {
            throw new \Exception('You already have an active attempt for this exam.', 409);
        }

        // 5. Calculate max_possible_score
        $maxPossibleScore = $exam->examQuestions()->sum('points');

        // 6. Create attempt
        $attempt = ExamAttempt::create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'attempt_number' => $attemptCount + 1,
            'status' => 'IN_PROGRESS',
            'reset_version' => 0,
            'active_session_token' => Str::uuid()->toString(),
            'last_heartbeat' => now(),
            'started_at' => now(),
            'max_possible_score' => $maxPossibleScore,
        ]);

        // 7. Load exam questions (NO points, NO correct answers)
        $exam->load([
            'examQuestions' => function ($query) {
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

        // 8. Calculate expiration time
        $expiresAt = $attempt->started_at->copy()->addMinutes($exam->duration_minutes);
        
        // Check for override
        $override = $exam->overrides()
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->first();
        
        if ($override && $override->override_ends_at) {
            $expiresAt = $override->override_ends_at;
        }

        return [
            'attempt_id' => $attempt->id,
            'session_token' => $attempt->active_session_token,
            'attempt_number' => $attempt->attempt_number,
            'started_at' => $attempt->started_at->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'duration_minutes' => $exam->duration_minutes,
            'reset_version' => $attempt->reset_version,
            'questions' => $exam->examQuestions->map(function ($examQuestion) {
                return [
                    'id' => $examQuestion->question->id,
                    'order_index' => $examQuestion->order_index,
                    'type' => $examQuestion->question->type,
                    'difficulty' => $examQuestion->question->difficulty,
                    'prompt_en' => $examQuestion->question->prompt_en,
                    'prompt_ar' => $examQuestion->question->prompt_ar,
                    'options' => $examQuestion->question->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'content_en' => $option->content_en,
                            'content_ar' => $option->content_ar,
                            'order_index' => $option->order_index,
                        ];
                    }),
                ];
            }),
        ];
    }

    /**
     * Process heartbeat
     * 
     * @param ExamAttempt $attempt
     * @param string $sessionToken
     * @param User $student
     * @return array
     * @throws \Exception
     */
    public function heartbeat(ExamAttempt $attempt, string $sessionToken, User $student): array
    {
        // Validate ownership and tenant
        if ($attempt->student_id !== $student->id || $attempt->school_id !== $student->school_id) {
            throw new \Exception('Unauthorized', 403);
        }

        // Validate session token
        if (!$attempt->isSessionValid($sessionToken)) {
            if ($attempt->isSessionStale()) {
                // Allow takeover
                $attempt->update(['active_session_token' => $sessionToken]);
            } else {
                throw new \Exception('Another session is active', 409);
            }
        }

        // Update heartbeat
        $attempt->update(['last_heartbeat' => now()]);

        // Calculate remaining time
        $remainingSeconds = $this->remainingSeconds($attempt, $attempt->exam);

        return [
            'status' => 'active',
            'time_remaining_seconds' => max(0, $remainingSeconds),
        ];
    }

    /**
     * Autosave answer
     * 
     * @param ExamAttempt $attempt
     * @param string $questionId
     * @param array $response
     * @param int $resetVersion
     * @param string $sessionToken
     * @param User $student
     * @param array|null $focusLog
     * @return array
     * @throws \Exception
     */
    public function autosave(
        ExamAttempt $attempt,
        string $questionId,
        array $response,
        int $resetVersion,
        string $sessionToken,
        User $student,
        ?array $focusLog = null
    ): array {
        // Validate ownership and tenant
        if ($attempt->student_id !== $student->id || $attempt->school_id !== $student->school_id) {
            throw new \Exception('Unauthorized', 403);
        }

        // Validate status
        if (!$attempt->isInProgress()) {
            throw new \Exception('Attempt is not in progress', 423);
        }

        // Validate session token
        if (!$attempt->isSessionValid($sessionToken)) {
            throw new \Exception('Session conflict', 409);
        }

        // Validate reset version
        if ($resetVersion !== $attempt->reset_version) {
            throw new \Exception('Invalid reset version', 422);
        }

        // Validate question belongs to exam
        $questionExists = $attempt->exam->examQuestions()
            ->where('question_id', $questionId)
            ->exists();

        if (!$questionExists) {
            throw new \Exception('Question does not belong to this exam', 422);
        }

        // Upsert answer (last-write-wins)
        AttemptAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'reset_version' => $resetVersion,
            ],
            [
                'student_response' => $response,
            ]
        );

        // Insert time log if provided
        if ($focusLog) {
            AnswerTimeLog::create([
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'reset_version' => $resetVersion,
                'start_time' => $focusLog['start_time'] ?? now(),
                'end_time' => $focusLog['end_time'] ?? now(),
                'duration_seconds' => $focusLog['duration_seconds'] ?? 0,
            ]);
        }

        // Update heartbeat
        $attempt->update(['last_heartbeat' => now()]);

        return [
            'saved' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Reset all answers
     * 
     * @param ExamAttempt $attempt
     * @param string $sessionToken
     * @param User $student
     * @return array
     * @throws \Exception
     */
    public function resetAll(ExamAttempt $attempt, string $sessionToken, User $student): array
    {
        // Validate ownership and tenant
        if ($attempt->student_id !== $student->id || $attempt->school_id !== $student->school_id) {
            throw new \Exception('Unauthorized', 403);
        }

        // Validate status
        if (!$attempt->isInProgress()) {
            throw new \Exception('Attempt is not in progress', 423);
        }

        // Validate session token
        if (!$attempt->isSessionValid($sessionToken)) {
            throw new \Exception('Session conflict', 409);
        }

        // Increment reset version
        $attempt->increment('reset_version');
        $attempt->refresh();

        return [
            'reset_version' => $attempt->reset_version,
            'message' => 'All answers have been cleared',
        ];
    }

    /**
     * Submit attempt
     * 
     * @param ExamAttempt $attempt
     * @param string $sessionToken
     * @param User $student
     * @return array
     * @throws \Exception
     */
    public function submit(ExamAttempt $attempt, string $sessionToken, User $student): array
    {
        // Validate ownership and tenant
        if ($attempt->student_id !== $student->id || $attempt->school_id !== $student->school_id) {
            throw new \Exception('Unauthorized', 403);
        }

        // Validate status
        if (!$attempt->isInProgress()) {
            throw new \Exception('Attempt already submitted', 422);
        }

        // Validate session token
        if (!$attempt->isSessionValid($sessionToken)) {
            throw new \Exception('Session conflict', 409);
        }

        // Check time limit
        $remainingSeconds = $this->remainingSeconds($attempt, $attempt->exam);
        if ($remainingSeconds < 0) {
            throw new \Exception('Time expired', 423);
        }

        // Update attempt status
        $attempt->update([
            'status' => 'SUBMITTED',
            'submitted_at' => now(),
            'active_session_token' => null,
        ]);

        // Auto-grade objective questions (MCQ, TF)
        $this->autoGradeObjectiveQuestions($attempt);

        // Determine final status
        $hasEssay = $attempt->exam->examQuestions()
            ->whereHas('question', function ($query) {
                $query->where('type', 'ESSAY');
            })
            ->exists();

        if ($hasEssay) {
            $attempt->update(['status' => 'PENDING_MANUAL']);
        } else {
            // Calculate scores
            $rawScore = $attempt->answers()
                ->where('reset_version', $attempt->reset_version)
                ->sum('points_awarded');
            
            $percentage = ($rawScore / $attempt->max_possible_score) * 100;

            $attempt->update([
                'status' => 'GRADED',
                'raw_score' => $rawScore,
                'percentage' => $percentage,
            ]);
        }

        // SECURITY: Do NOT return scores to student
        return [
            'submitted' => true,
            'submitted_at' => $attempt->submitted_at->toIso8601String(),
            'message' => 'Exam submitted successfully. Results will be available after grading.',
        ];
    }

    /**
     * Auto-grade objective questions (MCQ, TF)
     * 
     * @param ExamAttempt $attempt
     * @return void
     */
    protected function autoGradeObjectiveQuestions(ExamAttempt $attempt): void
    {
        $exam = $attempt->exam;

        foreach ($exam->examQuestions as $examQuestion) {
            $question = $examQuestion->question;

            // Skip ESSAY questions
            if ($question->type === 'ESSAY') {
                continue;
            }

            // Get student's answer
            $answer = AttemptAnswer::where('attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->where('reset_version', $attempt->reset_version)
                ->first();

            // If no answer, create one with 0 points
            if (!$answer) {
                AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'reset_version' => $attempt->reset_version,
                    'student_response' => ['selected_option_id' => null],
                    'points_awarded' => 0,
                ]);
                continue;
            }

            // Get correct option
            $correctOption = QuestionOption::where('question_id', $question->id)
                ->where('is_correct', true)
                ->first();

            if (!$correctOption) {
                // No correct option defined, award 0 points
                $answer->update(['points_awarded' => 0]);
                continue;
            }

            // Compare selected option with correct option
            $selectedOptionId = $answer->student_response['selected_option_id'] ?? null;

            if ($selectedOptionId === $correctOption->id) {
                $answer->update(['points_awarded' => $examQuestion->points]);
            } else {
                $answer->update(['points_awarded' => 0]);
            }
        }
    }

    /**
     * Calculate remaining seconds
     * 
     * @param ExamAttempt $attempt
     * @param Exam $exam
     * @return int
     */
    public function remainingSeconds(ExamAttempt $attempt, Exam $exam): int
    {
        $expiresAt = $attempt->started_at->copy()->addMinutes($exam->duration_minutes);

        // Check for override
        $override = $exam->overrides()
            ->where('student_id', $attempt->student_id)
            ->where('school_id', $attempt->school_id)
            ->first();

        if ($override && $override->override_ends_at) {
            $expiresAt = $override->override_ends_at;
        }

        return now()->diffInSeconds($expiresAt, false);
    }
}
