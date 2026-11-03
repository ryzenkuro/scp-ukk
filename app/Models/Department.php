<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_code',
        'department_name',
        'description',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'department_id');
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(Schedule::class, SchoolClass::class, 'department_id', 'class_id');
    }
}

