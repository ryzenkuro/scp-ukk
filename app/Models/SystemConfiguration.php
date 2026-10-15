<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked' => 'boolean',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => 1,
            ]
        );
    }
}
