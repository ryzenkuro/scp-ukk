<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $fillable = [
        'schedule_id',
        'teacher_id',
        'date',
        'topic',
        'notes',
        'documentation_path',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'documentation_url',
    ];

    public function getDocumentationUrlAttribute(): ?string
    {
        return $this->documentation_path ? asset('storage/'.$this->documentation_path) : null;
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
