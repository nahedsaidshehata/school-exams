<?php

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Models\QuestionOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoSubmitExpiredAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attempts:auto-submit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically submit exam attempts that have exceeded their time limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-submit process...');

        // Find all IN_PROGRESS attempts that have exceeded their time limit
        $expiredAttempts = ExamAttempt::with('exam')
            ->where('status', 'IN_PROGRESS')
            ->whereNotNull('started_at')
            ->get()
            ->filter(function ($attempt) {
                // Calculate expiration time: started_at + duration_minutes
                $expiresAt = $attempt->started_at->copy()->addMinutes($attempt->exam->duration_minutes);
                
                // Check if current time is past expiration
                return now()->greaterThan($expiresAt);
            });

        if ($expiredAttempts->isEmpty()) {
            $this->info('No expired attempts found.');
            return 0;
        }

        $this->info("Found {$expiredAttempts->count()} expired attempt(s).");

        $successCount = 0;
        $errorCount = 0;

        foreach ($expiredAttempts as $attempt) {
            try {
                DB::beginTransaction();

                // Double-check status (idempotent - prevent race conditions)
                $attempt->refresh();
                if ($attempt->status !== 'IN_PROGRESS') {
                    $this->warn("Attempt {$attempt->id} already processed. Skipping.");
                    DB::rollBack();
                    continue;
                }

                // Update attempt to SUBMITTED
                $attempt->update([
                    'status' => 'SUBMITTED',
                    'submitted_at' => now(),
                    'active_session_token' => null,
                ]);

                // Auto-grade objective questions (MCQ, TF)
                $this->autoGradeObjectiveQuestions($attempt);

                // Determine final status based on essay questions
                $hasEssay = $attempt->exam->examQuestions()
                    ->whereHas('question', function ($query) {
                        $query->where('type', 'ESSAY');
                    })
                    ->exists();

                if ($hasEssay) {
                    $attempt->update(['status' => 'PENDING_MANUAL']);
                    $this->info("Attempt {$attempt->id} submitted and marked as PENDING_MANUAL (has essay questions).");
                } else {
                    // Calculate final scores
                    $rawScore = $attempt->answers()
                        ->where('reset_version', $attempt->reset_version)
                        ->sum('points_awarded');
                    
                    $percentage = $attempt->max_possible_score > 0 
                        ? ($rawScore / $attempt->max_possible_score) * 100 
                        : 0;

                    $attempt->update([
                        'status' => 'GRADED',
                        'raw_score' => $rawScore,
                        'percentage' => $percentage,
                    ]);
                    
                    $this->info("Attempt {$attempt->id} submitted and auto-graded. Score: {$rawScore}/{$attempt->max_possible_score}");
                }

                DB::commit();
                $successCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $errorCount++;
                
                $this->error("Failed to auto-submit attempt {$attempt->id}: {$e->getMessage()}");
                Log::error("Auto-submit failed for attempt {$attempt->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Auto-submit completed: {$successCount} successful, {$errorCount} failed.");
        
        return 0;
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
}
