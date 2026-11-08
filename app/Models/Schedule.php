<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'cycle_number',
        'subject',
        'teacher_id',
        'class_name',
        'class_id',
        'room_name',
        'room_id',
        'start_time',
        'end_time',
        'start_slot_id',
        'end_slot_id',
        'academic_year',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function startSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'start_slot_id');
    }

    public function endSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'end_slot_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}

