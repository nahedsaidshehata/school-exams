<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'section_id',
        'title_en',
        'title_ar',
        'content_ar',
        'content_en',
        'grade',
        'content_updated_at',
    ];


    /**
     * Get the section that owns this lesson.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the questions for this lesson.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Learning outcomes linked to this lesson (pivot: lesson_learning_outcome)
     */
    public function learningOutcomes(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\LearningOutcome::class,
            'lesson_learning_outcome',
            'lesson_id',
            'learning_outcome_id'
        )->withTimestamps();
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\LessonAttachment::class);
    }

}
