<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alumni extends Model
{
    protected $fillable = [
        'student_id',
        'nis',
        'name',
        'jurusan',
        'kelas_terakhir',
        'tahun_lulus',
        'tanggal_lulus',
        'alamat',
        'phone',
        'email',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_lulus' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_expired',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    /**
     * Memeriksa apakah data alumni sudah expired (melebihi 1 tahun sejak tahun kelulusan)
     */
    public function getIsExpiredAttribute(): bool
    {
        $currentYear = (int) date('Y');
        $gradYear = (int) $this->tahun_lulus;

        return $currentYear > ($gradYear + 1);
    }
}
