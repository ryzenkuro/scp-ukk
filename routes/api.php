<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Guru\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Laravel Sanctum Bearer Token)
|--------------------------------------------------------------------------
|
| Rute REST API terstandarisasi untuk integrasi mobile client atau pengujian
| otomatis menggunakan Postman / HTTP Client dengan Bearer Token.
|
*/

// 1. Endpoint Autentikasi Publik API (Login Sanctum)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// 2. Endpoint Privat API (Memerlukan Bearer Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Endpoint Jadwal Guru (Uji Anti-Bentrok & Proteksi Lock Schedule via Postman)
    Route::middleware(['role:guru', 'schedule.unlocked'])->group(function () {
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('api.schedules.store');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('api.schedules.destroy');
    });
});
