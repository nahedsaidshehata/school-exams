<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'reset_version',
        'student_response',
        'points_awarded',
    ];

    protected $casts = [
        'student_response' => 'array',
        'points_awarded' => 'decimal:2',
    ];

    /**
     * Get the attempt that owns the answer
     */
    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    /**
     * Get the question for this answer
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
