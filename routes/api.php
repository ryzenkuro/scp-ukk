<?php

use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JournalApiController;
use App\Http\Controllers\Api\LeaveRequestApiController;
use App\Http\Controllers\Api\ScheduleApiController;
use App\Http\Controllers\Api\SiswaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Endpoints
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public / Read Endpoints
Route::get('/siswas', [SiswaApiController::class, 'index']);
Route::get('/siswas/{id}', [SiswaApiController::class, 'show']);

Route::get('/schedules', [ScheduleApiController::class, 'index']);
Route::get('/schedules/{id}', [ScheduleApiController::class, 'show']);

Route::get('/attendances', [AttendanceApiController::class, 'index']);
Route::get('/attendances/{id}', [AttendanceApiController::class, 'show']);

Route::get('/journals', [JournalApiController::class, 'index']);
Route::get('/journals/{id}', [JournalApiController::class, 'show']);

Route::get('/leave-requests', [LeaveRequestApiController::class, 'index']);
Route::get('/leave-requests/{id}', [LeaveRequestApiController::class, 'show']);

// Sanctum Protected Endpoints
Route::middleware(['auth:sanctum', 'active.account'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/force-change-password', [AuthController::class, 'forceChangePassword']);

    // Siswa CRUD (Protected)
    Route::post('/siswas', [SiswaApiController::class, 'store']);
    Route::put('/siswas/{id}', [SiswaApiController::class, 'update']);
    Route::delete('/siswas/{id}', [SiswaApiController::class, 'destroy']);

    // Presensi & Jurnal (Protected)
    Route::post('/attendances', [AttendanceApiController::class, 'store']);
    Route::post('/journals', [JournalApiController::class, 'store']);

    // Pengajuan Izin (Protected)
    Route::post('/leave-requests', [LeaveRequestApiController::class, 'store']);
    Route::put('/leave-requests/{id}/status', [LeaveRequestApiController::class, 'updateStatus']);
});
