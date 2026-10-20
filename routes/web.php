<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Waka\SystemConfigController;
use App\Http\Controllers\Waka\WakaDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Redirection Global Dashboard berdasarkan Role
Route::get('/dashboard', function () {
    $role = auth()->user()->role?->role_name;

    return match ($role) {
        'super_admin', 'waka_kurikulum' => redirect('/admin'),
        'guru'                          => redirect()->route('guru.dashboard'),
        'wali_kelas', 'guru_bk'         => redirect()->route('kesiswaan.dashboard'),
        'siswa', 'orang_tua'            => redirect()->route('portal.dashboard'),
        default                         => abort(403, 'Peran pengguna tidak dikenali.'),
    };
})->middleware(['auth'])->name('dashboard');

// 1. Panel Waka Kurikulum (Master Data & Siklus Blok)
Route::middleware(['auth', 'role:waka_kurikulum'])->prefix('waka')->name('waka.')->group(function () {
    Route::get('/dashboard', [WakaDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/config/toggle-cycle', [SystemConfigController::class, 'toggleCycle'])->name('config.toggle-cycle');
    Route::patch('/config/toggle-lock', [SystemConfigController::class, 'toggleLock'])->name('config.toggle-lock');

    Route::post('/classes', [WakaDashboardController::class, 'storeClass'])->name('classes.store');
    Route::post('/rooms', [WakaDashboardController::class, 'storeRoom'])->name('rooms.store');
    Route::post('/users', [WakaDashboardController::class, 'storeUser'])->name('users.store');
});

// 2. Portal Guru
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Guru\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/schedules', [\App\Http\Controllers\Guru\ScheduleController::class, 'index'])->name('schedules.index');

    // Modifikasi jadwal (tambah & hapus) wajib melalui pengecekan apakah jadwal sedang dikunci
    Route::middleware('schedule.unlocked')->group(function () {
        Route::post('/schedules', [\App\Http\Controllers\Guru\ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/schedules/{schedule}', [\App\Http\Controllers\Guru\ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // Modul Jurnal Mengajar & Presensi (Fase 5)
    Route::get('/journals', [\App\Http\Controllers\Guru\JournalController::class, 'index'])->name('journals.index');
    Route::get('/schedules/{schedule}/journals/create', [\App\Http\Controllers\Guru\JournalController::class, 'create'])->name('journals.create');
    Route::post('/schedules/{schedule}/journals', [\App\Http\Controllers\Guru\JournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{journal}', [\App\Http\Controllers\Guru\JournalController::class, 'show'])->name('journals.show');
});


// 3. Panel Tim Kesiswaan (Wali Kelas & Guru BK)
Route::middleware(['auth', 'role:wali_kelas,guru_bk'])->prefix('kesiswaan')->name('kesiswaan.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Kesiswaan\KesiswaanController::class, 'index'])->name('dashboard');
    Route::patch('/leave-requests/{leaveRequest}/verify', [\App\Http\Controllers\Kesiswaan\KesiswaanController::class, 'verify'])->name('leave-requests.verify');
});

// 4. Portal Siswa & Orang Tua
Route::middleware(['auth', 'role:siswa,orang_tua'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Portal\PortalController::class, 'index'])->name('dashboard');
    Route::post('/leave-requests', [\App\Http\Controllers\Portal\PortalController::class, 'storeLeave'])->name('leave-requests.store');
});

// Profil User Bawaan Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
