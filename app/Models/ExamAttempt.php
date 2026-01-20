<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'school_id',
        'student_id',
        'exam_id',
        'attempt_number',
        'status',
        'reset_version',
        'active_session_token',
        'last_heartbeat',
        'started_at',
        'submitted_at',
        'max_possible_score',
        'raw_score',
        'percentage',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_heartbeat' => 'datetime',
        'max_possible_score' => 'decimal:2',
        'raw_score' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the school that owns the attempt
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the student that owns the attempt
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the exam for this attempt
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the answers for this attempt
     */
    public function answers()
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }

    /**
     * Get the time logs for this attempt
     */
    public function timeLogs()
    {
        return $this->hasMany(AnswerTimeLog::class, 'attempt_id');
    }

    /**
     * Check if session token is valid
     */
    public function isSessionValid(string $token): bool
    {
        return $this->active_session_token === $token;
    }

    /**
     * Check if session is stale (> 5 minutes since last heartbeat)
     */
    public function isSessionStale(): bool
    {
        if (!$this->last_heartbeat) {
            return true;
        }
        
        return $this->last_heartbeat->diffInMinutes(now()) > 5;
    }

    /**
     * Check if attempt is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }

    /**
     * Check if attempt is submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, ['SUBMITTED', 'PENDING_MANUAL', 'GRADED']);
    }
}
