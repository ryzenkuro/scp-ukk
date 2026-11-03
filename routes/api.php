<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalApiController;
use App\Http\Controllers\Api\LeaveApprovalApiController;
use App\Http\Controllers\Api\StudentPortalApiController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Guru\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Laravel Sanctum Bearer Token)
|--------------------------------------------------------------------------
|
| Rute REST API terstandarisasi untuk integrasi mobile client (Flutter)
| atau pengujian otomatis menggunakan Postman / HTTP Client.
|
*/

// 1. Endpoint Autentikasi Publik API (Login Sanctum & Pemulihan Kata Sandi Mandiri)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/forgot-password/verify', [PasswordResetLinkController::class, 'verify'])->name('api.forgot-password.verify');
Route::post('/forgot-password/reset', [PasswordResetLinkController::class, 'reset'])->name('api.forgot-password.reset');

// 2. Endpoint Privat API (Memerlukan Bearer Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Endpoint Operasional Guru & Waka Kurikulum (Jadwal, Jurnal KBM TeFa & Presensi Kelas - Fase 2 Mobile)
    Route::middleware('role:guru,waka_kurikulum')->prefix('guru')->group(function () {
        Route::get('/schedules', [JournalApiController::class, 'schedules'])->name('api.guru.schedules');
        Route::get('/schedules/{schedule}/students', [JournalApiController::class, 'scheduleStudents'])->name('api.guru.schedules.students');
        Route::get('/journals', [JournalApiController::class, 'index'])->name('api.guru.journals.index');
        Route::post('/journals', [JournalApiController::class, 'store'])->name('api.guru.journals.store');
        Route::get('/journals/{journal}', [JournalApiController::class, 'show'])->name('api.guru.journals.show');
    });

    // Endpoint Operasional Siswa, Orang Tua, Wali Kelas, Guru BK & Waka Kurikulum (Jadwal Blok, Rekap Presensi & Pengajuan Izin - Fase 3 Mobile)
    Route::middleware('role:siswa,orang_tua,wali_kelas,guru_bk,waka_kurikulum')->prefix('portal')->group(function () {
        Route::get('/schedules', [StudentPortalApiController::class, 'schedules'])->name('api.portal.schedules');
        Route::get('/attendance/summary', [StudentPortalApiController::class, 'attendanceSummary'])->name('api.portal.attendance.summary');
        Route::get('/leave-requests', [StudentPortalApiController::class, 'leaveRequests'])->name('api.portal.leave-requests.index');
        Route::post('/leave-requests', [StudentPortalApiController::class, 'storeLeaveRequest'])->name('api.portal.leave-requests.store');
        Route::get('/children', [StudentPortalApiController::class, 'children'])->name('api.portal.children');
    });

    // Endpoint Verifikasi Izin Siswa (Wali Kelas & Guru BK - Fase 4 Mobile)
    Route::middleware('role:wali_kelas,guru_bk,waka_kurikulum')->prefix('approval')->group(function () {
        Route::get('/leave-requests', [LeaveApprovalApiController::class, 'index'])->name('api.approval.leave-requests.index');
        Route::patch('/leave-requests/{leaveRequest}', [LeaveApprovalApiController::class, 'verify'])->name('api.approval.leave-requests.verify');
    });

    // Endpoint Jadwal Guru (Uji Anti-Bentrok & Proteksi Lock Schedule via Postman - KF76-KF80)
    Route::middleware('role:guru')->group(function () {
        Route::get('/schedules', [ScheduleController::class, 'apiIndex'])->name('api.schedules.index');

        Route::middleware('schedule.unlocked')->group(function () {
            Route::post('/schedules', [ScheduleController::class, 'store'])->name('api.schedules.store');
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('api.schedules.destroy');
        });
    });
});
