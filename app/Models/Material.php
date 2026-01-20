<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    /**
     * Get the sections for this material.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
