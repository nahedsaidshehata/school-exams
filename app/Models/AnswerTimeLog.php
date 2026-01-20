<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerTimeLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'reset_version',
        'start_time',
        'end_time',
        'duration_seconds',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the attempt that owns the time log
     */
    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    /**
     * Get the question for this time log
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
