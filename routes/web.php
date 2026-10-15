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
        'waka_kurikulum'        => redirect()->route('waka.dashboard'),
        'guru'                  => redirect()->route('guru.dashboard'),
        'wali_kelas', 'guru_bk' => redirect()->route('kesiswaan.dashboard'),
        'siswa', 'orang_tua'    => redirect()->route('portal.dashboard'),
        default                 => abort(403, 'Peran pengguna tidak dikenali.'),
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
    Route::get('/dashboard', function () {
        return view('guru.dashboard');
    })->name('dashboard');

    Route::get('/schedules', [\App\Http\Controllers\Guru\ScheduleController::class, 'index'])->name('schedules.index');

    // Modifikasi jadwal (tambah & hapus) wajib melalui pengecekan apakah jadwal sedang dikunci
    Route::middleware('schedule.unlocked')->group(function () {
        Route::post('/schedules', [\App\Http\Controllers\Guru\ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/schedules/{schedule}', [\App\Http\Controllers\Guru\ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });
});

// Endpoint API Jadwal (Proteksi Lock Schedule untuk Postman/REST API)
Route::middleware(['auth', 'role:guru', 'schedule.unlocked'])->prefix('api')->name('api.')->group(function () {
    Route::post('/schedules', [\App\Http\Controllers\Guru\ScheduleController::class, 'store'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [\App\Http\Controllers\Guru\ScheduleController::class, 'destroy'])->name('schedules.destroy');
});

// 3. Panel Tim Kesiswaan (Wali Kelas & Guru BK)
Route::middleware(['auth', 'role:wali_kelas,guru_bk'])->prefix('kesiswaan')->name('kesiswaan.')->group(function () {
    Route::get('/dashboard', function () {
        return view('kesiswaan.dashboard');
    })->name('dashboard');
});

// 4. Portal Siswa & Orang Tua
Route::middleware(['auth', 'role:siswa,orang_tua'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', function () {
        return view('portal.dashboard');
    })->name('dashboard');
});

// Profil User Bawaan Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
