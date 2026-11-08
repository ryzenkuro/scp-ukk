<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeroomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_class_id',
        'class_name',
        'academic_year',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }
}

