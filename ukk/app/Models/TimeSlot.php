<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_break',
        'order',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'order' => 'integer',
    ];
}

