<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'material_id',
        'title_en',
        'title_ar',
    ];

    /**
     * Get the material that owns this section.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the lessons for this section.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
