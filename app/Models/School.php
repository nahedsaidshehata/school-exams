<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    /**
     * Get the school account user for this school.
     */
    public function schoolUser(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'school');
    }

    /**
     * Get all students for this school.
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'student');
    }

    /**
     * Get all users for this school.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
