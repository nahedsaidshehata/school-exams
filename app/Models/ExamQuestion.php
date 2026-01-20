<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'exam_id',
        'question_id',
        'points',
        'order_index',
    ];

    protected $casts = [
        'points' => 'decimal:2',
    ];

    /**
     * Get the exam that owns this exam question
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the question that this exam question references
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
