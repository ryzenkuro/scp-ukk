<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'username',
        'fullname',
        'email',
        'phone',
        'nip',
        'password',
        'must_change_credentials',
        'is_active',
        'student_id',
        'class_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_credentials' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'teacher_id');
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'user_id');
    }

    public function isWaka(): bool
    {
        return in_array($this->role?->name, ['waka', 'admin'], true);
    }

    public function isGuru(): bool
    {
        return $this->role?->name === 'guru';
    }

    public function isGuruBk(): bool
    {
        return in_array($this->role?->name, ['guru_bk', 'bk', 'kesiswaan'], true) || (int) $this->role_id === 4;
    }

    public function isKesiswaan(): bool
    {
        return $this->isGuruBk();
    }

    public function isWaliKelas(): bool
    {
        return $this->role?->name === 'wali_kelas' || (int) $this->role_id === 5 || $this->homeroomAssignments()->exists() || \Illuminate\Support\Facades\DB::table('user_school_classes')->where('user_id', $this->id)->exists();
    }

    public function getWaliClasses(): array
    {
        $fromPivot = \Illuminate\Support\Facades\DB::table('user_school_classes')
            ->join('school_classes', 'user_school_classes.school_class_id', '=', 'school_classes.id')
            ->where('user_school_classes.user_id', $this->id)
            ->pluck('school_classes.name')
            ->values()
            ->all();

        if (!empty($fromPivot)) {
            return $fromPivot;
        }

        return $this->homeroomAssignments()->pluck('class_name')->values()->all();
    }

    public function isSiswa(): bool
    {
        return $this->role?->name === 'siswa';
    }

    public function isOrangTua(): bool
    {
        return in_array($this->role?->name, ['orang_tua', 'ortu'], true);
    }
}
