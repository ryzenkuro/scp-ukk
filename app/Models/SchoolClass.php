<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'department_id',
        'grade',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id')->whereHas('role', fn ($q) => $q->where('role_name', 'siswa'));
    }

    public function homeroomTeacher(): HasOne
    {
        return $this->hasOne(User::class, 'class_id')->whereHas('role', fn ($q) => $q->where('role_name', 'wali_kelas'));
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
}
