<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_MCQ = 'MCQ';
    public const TYPE_TF = 'TF';
    public const TYPE_ESSAY = 'ESSAY';
    public const TYPE_CLASSIFICATION = 'CLASSIFICATION';
    public const TYPE_REORDER = 'REORDER';
    public const TYPE_FILL_BLANK = 'FILL_BLANK';

    public function isChoiceBased(): bool
    {
        return in_array($this->type, [self::TYPE_MCQ, self::TYPE_TF], true);
    }

    /**
     * ✅ Only MCQ/TF use QuestionOption rows.
     * REORDER/CLASSIFICATION/ESSAY should be stored in metadata.
     */
    public function usesOptionsList(): bool
    {
        return in_array($this->type, [self::TYPE_MCQ, self::TYPE_TF], true);
    }

    protected $fillable = [
        'lesson_id',
        'type',
        'difficulty',
        'prompt_en',
        'prompt_ar',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('id', 'points', 'order_index')
            ->withTimestamps()
            ->orderBy('exam_questions.order_index');
    }

    public function learningOutcomes()
    {
        return $this->belongsToMany(\App\Models\LearningOutcome::class, 'question_learning_outcome')
            ->withPivot(['coverage_level'])
            ->withTimestamps();
    }
}
