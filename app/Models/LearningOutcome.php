<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class LearningOutcome extends Model
{
    protected $table = 'learning_outcomes';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'material_id',
        'section_id',
        'code',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'grade_level',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (!$m->id) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Lesson::class,
            'lesson_learning_outcome',
            'learning_outcome_id',
            'lesson_id'
        )->withTimestamps();
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_learning_outcome')
            ->withPivot(['coverage_level'])
            ->withTimestamps();
    }
}
