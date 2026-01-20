<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title_en',
        'title_ar',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'max_attempts',
        'is_globally_locked',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_globally_locked' => 'boolean',
    ];

    /**
     * Get the questions attached to this exam
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order_index');
    }

    /**
     * Get the questions for this exam through the pivot table
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('id', 'points', 'order_index')
            ->withTimestamps()
            ->orderBy('exam_questions.order_index');
    }

    /**
     * Get the assignments for this exam
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    /**
     * Get the overrides for this exam
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(ExamOverride::class);
    }

    /**
     * Get total points for this exam
     */
    public function getTotalPointsAttribute(): float
    {
        return $this->examQuestions()->sum('points');
    }

    /**
     * Get questions count for this exam
     */
    public function getQuestionsCountAttribute(): int
    {
        return $this->examQuestions()->count();
    }
}
