<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends Model
{
    protected $fillable = [
        'nis',
        'name',
        'kelas',
        'jurusan',
        'angkatan',
        'gender',
        'alamat',
        'phone',
        'email',
        'status',
        'tahun_lulus',
        'tanggal_lulus',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'student_id')->where('role_id', 3);
    }

    public function parentUser(): HasOne
    {
        return $this->hasOne(User::class, 'student_id')->where('role_id', 6);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }
}

