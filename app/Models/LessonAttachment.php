<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonAttachment extends Model
{
    protected $fillable = [
        'lesson_id',
        'uploaded_by',
        'original_name',
        'mime_type',
        'extension',
        'disk',
        'path',
        'size_bytes',
        'sha256',

        // extraction
        'extraction_status',
        'extraction_error',
        'extracted_text',
        'extracted_text_updated_at',
    ];

    protected $casts = [
        'extracted_text_updated_at' => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
