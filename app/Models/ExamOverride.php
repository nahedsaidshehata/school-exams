<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamOverride extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'exam_id',
        'school_id',
        'student_id',
        'lock_mode',
        'override_ends_at',
    ];

    protected $casts = [
        'override_ends_at' => 'datetime',
    ];

    /**
     * Get the exam that owns this override
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the school that this override belongs to
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the student that this override applies to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
