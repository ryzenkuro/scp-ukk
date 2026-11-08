<?php

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\JournalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Waka\DashboardController as WakaDashboardController;
use App\Http\Controllers\Waka\TimeSlotController;
use App\Http\Controllers\Waka\SiswaController;
use App\Http\Controllers\Waka\AlumniController;
use App\Http\Controllers\Waka\GuruController;
use App\Http\Controllers\Waka\OrangTuaController;
use App\Http\Controllers\Waka\AkunRoleController;
use App\Http\Controllers\Waka\JadwalController;
use App\Http\Controllers\Waka\LaporanController;
use App\Http\Controllers\Siswa\PortalController as SiswaPortalController;
use App\Http\Controllers\Kesiswaan\KesiswaanPortalController;
use App\Http\Controllers\WaliKelas\WaliKelasPortalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing page: langsung arahkan ke Login jika belum masuk, atau ke Dashboard jika sudah login
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Redirection Global Dashboard berdasarkan Role Pengguna
Route::get('/dashboard', function () {
    $user = auth()->user();
    $role = $user->role?->name ?? 'waka';

    return match ($role) {
        'waka' => redirect()->route('waka.dashboard'),
        'guru_bk', 'bk', 'kesiswaan' => redirect()->route('bk.dashboard'),
        'wali_kelas' => redirect()->route('wali-kelas.dashboard'),
        'guru' => $user->isWaliKelas() ? redirect()->route('wali-kelas.dashboard') : redirect()->route('guru.dashboard'),
        'siswa', 'orang_tua' => redirect()->route('siswa.dashboard'),
        default => redirect()->route('waka.dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

// Alur Aktivasi Akun Mandiri (Kustomisasi Username & Password Sendiri)
Route::middleware(['auth'])->group(function () {
    Route::get('/auth/activate', [AccountActivationController::class, 'show'])->name('auth.activate');
    Route::post('/auth/activate', [AccountActivationController::class, 'store'])->name('auth.activate.store');
});

// Panel Waka Kurikulum (Admin Utama)
Route::middleware(['auth', 'role:waka'])->prefix('waka')->name('waka.')->group(function () {
    Route::get('/dashboard', [WakaDashboardController::class, 'index'])->name('dashboard');
    Route::post('/config/toggle-cycle', [WakaDashboardController::class, 'toggleCycle'])->name('config.toggle-cycle');
    Route::post('/config/toggle-lock', [WakaDashboardController::class, 'toggleLock'])->name('config.toggle-lock');
    Route::post('/config/advance-academic-year', [WakaDashboardController::class, 'advanceAcademicYear'])->name('config.advance-academic-year');

    // Master Jam Pembelajaran Sekolah
    Route::get('/jam-pelajaran/template', [TimeSlotController::class, 'template'])->name('jam-pelajaran.template');
    Route::get('/jam-pelajaran/contoh', [TimeSlotController::class, 'contoh'])->name('jam-pelajaran.contoh');
    Route::post('/jam-pelajaran/import', [TimeSlotController::class, 'import'])->name('jam-pelajaran.import');
    Route::delete('/jam-pelajaran/reset-semua', [TimeSlotController::class, 'resetAll'])->name('jam-pelajaran.reset-all');
    Route::get('/jam-pelajaran', [TimeSlotController::class, 'index'])->name('jam-pelajaran.index');
    Route::post('/jam-pelajaran', [TimeSlotController::class, 'store'])->name('jam-pelajaran.store');
    Route::put('/jam-pelajaran/{timeSlot}', [TimeSlotController::class, 'update'])->name('jam-pelajaran.update');
    Route::delete('/jam-pelajaran/{timeSlot}', [TimeSlotController::class, 'destroy'])->name('jam-pelajaran.destroy');

    // Data Master: Siswa & Kenaikan Kelas per Angkatan
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa');
    Route::get('/siswa-index', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/template', [SiswaController::class, 'template'])->name('siswa.template');
    Route::get('/siswa/contoh', [SiswaController::class, 'contoh'])->name('siswa.example');
    Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    Route::delete('/siswa/hapus-per-kelas', [SiswaController::class, 'hapusPerKelas'])->name('siswa.destroy_class');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
    Route::post('/siswa/naik-kelas', [SiswaController::class, 'naikKelas'])->name('siswa.naik-kelas');

    // Aliases for data-siswa
    Route::get('/data-siswa', [SiswaController::class, 'index']);
    Route::get('/data-siswa/template', [SiswaController::class, 'template']);
    Route::get('/data-siswa/contoh', [SiswaController::class, 'contoh']);
    Route::post('/data-siswa/import', [SiswaController::class, 'import']);
    Route::delete('/data-siswa/hapus-per-kelas', [SiswaController::class, 'hapusPerKelas']);

    // Data Master: Alumni (Arsip Kelulusan & Retensi Otomatis 1 Tahun)
    Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni');
    Route::get('/alumni-index', [AlumniController::class, 'index'])->name('alumni.index');

    // Data Master: Tenaga Pendidik (Guru)
    Route::get('/guru', [GuruController::class, 'index'])->name('guru');
    Route::get('/guru-index', [GuruController::class, 'index'])->name('guru.index');
    Route::get('/guru/template', [GuruController::class, 'template'])->name('guru.template');
    Route::get('/guru/contoh', [GuruController::class, 'contoh'])->name('guru.example');
    Route::post('/guru/import', [GuruController::class, 'import'])->name('guru.import');
    Route::post('/guru', [GuruController::class, 'store'])->name('guru.store');
    Route::put('/guru/{guru}', [GuruController::class, 'update'])->name('guru.update');
    Route::delete('/guru/reset-semua', [GuruController::class, 'resetAll'])->name('guru.reset-all');
    Route::delete('/guru/{guru}', [GuruController::class, 'destroy'])->name('guru.destroy');
    Route::post('/guru/{guru}/reset-password', [GuruController::class, 'resetPassword'])->name('guru.reset-password');

    // Aliases for data-guru
    Route::get('/data-guru', [GuruController::class, 'index']);
    Route::get('/data-guru/template', [GuruController::class, 'template']);
    Route::get('/data-guru/contoh', [GuruController::class, 'contoh']);
    Route::post('/data-guru/import', [GuruController::class, 'import']);

    // Data Master: Orang Tua Murid
    Route::get('/orang-tua', [OrangTuaController::class, 'index'])->name('orang-tua.index');
    Route::put('/orang-tua/{orangTua}', [OrangTuaController::class, 'update'])->name('orang-tua.update');
    Route::post('/orang-tua/{orangTua}/reset-password', [OrangTuaController::class, 'resetPassword'])->name('orang-tua.reset-password');

    // Jadwal Sekolah & Proteksi Kunci Putaran
    Route::get('/jadwal', [JadwalController::class, 'index'])->name('jadwal');
    Route::get('/jadwal-index', [JadwalController::class, 'index'])->name('jadwal.index');
    Route::get('/jadwal/template', [JadwalController::class, 'template'])->name('jadwal.template');
    Route::get('/jadwal/contoh', [JadwalController::class, 'contoh'])->name('jadwal.example');
    Route::post('/jadwal/import', [JadwalController::class, 'import'])->name('jadwal.import');
    Route::get('/jadwal/export-pdf', [JadwalController::class, 'exportPdf'])->name('jadwal.pdf');
    Route::post('/jadwal', [JadwalController::class, 'store'])->name('jadwal.store');
    Route::put('/jadwal/{jadwal}', [JadwalController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/reset-semua', [JadwalController::class, 'resetAll'])->name('jadwal.reset-all');
    Route::delete('/jadwal/{jadwal}', [JadwalController::class, 'destroy'])->name('jadwal.destroy');

    // Kelola Akun & Role
    Route::get('/akun', [AkunRoleController::class, 'index'])->name('akun.index');
    Route::post('/akun', [AkunRoleController::class, 'store'])->name('akun.store');
    Route::post('/akun/ganti-waka', [AkunRoleController::class, 'gantiWaka'])->name('akun.ganti-waka');
    Route::delete('/akun/{user}', [AkunRoleController::class, 'destroy'])->name('akun.destroy');
    Route::post('/akun/{user}/toggle-active', [AkunRoleController::class, 'toggleActive'])->name('akun.toggle-active');
    Route::post('/akun/{user}/reset-credentials', [AkunRoleController::class, 'resetCredentials'])->name('akun.reset-credentials');

    // Laporan Rekapitulasi Presensi, Izin & Audit Kinerja Guru
    Route::get('/laporan/presensi', [LaporanController::class, 'presensi'])->name('laporan.presensi');
    Route::get('/laporan/izin', [LaporanController::class, 'izin'])->name('laporan.izin');
    Route::post('/laporan/izin/{leave}/verify', [LaporanController::class, 'verifyIzin'])->name('laporan.izin.verify');
    Route::get('/laporan/guru', [LaporanController::class, 'guru'])->name('laporan.guru');
    Route::get('/laporan/guru/{guru}', [LaporanController::class, 'guruDetail'])->name('laporan.guru.detail');
});

// Portal Guru
Route::middleware(['auth', 'role:guru,wali_kelas,guru_bk,kesiswaan'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::get('/jadwal/export-pdf', [GuruDashboardController::class, 'exportPdf'])->name('jadwal.pdf');

    // Operasional KBM: Jurnal Mengajar & Presensi Siswa (CRUD Penuh)
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/create', [JournalController::class, 'create'])->name('journal.create');
    Route::post('/journal', [JournalController::class, 'store'])->name('journal.store');
    Route::get('/journal/{journal}/edit', [JournalController::class, 'edit'])->name('journal.edit');
    Route::put('/journal/{journal}', [JournalController::class, 'update'])->name('journal.update');
    Route::delete('/journal/{journal}', [JournalController::class, 'destroy'])->name('journal.destroy');
});

// Portal Siswa & Orang Tua
Route::middleware(['auth', 'role:siswa,orang_tua'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/jadwal', [SiswaPortalController::class, 'jadwal'])->name('jadwal');
    Route::get('/jadwal/export-pdf', [SiswaPortalController::class, 'exportPdf'])->name('jadwal.pdf');
    Route::get('/izin', [SiswaPortalController::class, 'izin'])->name('izin');
    Route::post('/izin', [SiswaPortalController::class, 'submitIzin'])->name('izin.store');
});

// Portal Guru BK
Route::middleware(['auth', 'role:guru_bk,kesiswaan'])->prefix('bk')->name('bk.')->group(function () {
    Route::get('/dashboard', [KesiswaanPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/presensi', [KesiswaanPortalController::class, 'presensi'])->name('presensi');
    Route::get('/izin', [KesiswaanPortalController::class, 'izin'])->name('izin');
});

// Alias backward compatibility untuk rute /kesiswaan
Route::middleware(['auth', 'role:guru_bk,kesiswaan'])->prefix('kesiswaan')->name('kesiswaan.')->group(function () {
    Route::get('/dashboard', [KesiswaanPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/presensi', [KesiswaanPortalController::class, 'presensi'])->name('presensi');
    Route::get('/izin', [KesiswaanPortalController::class, 'izin'])->name('izin');
});

// Portal Wali Kelas
Route::middleware(['auth', 'role:wali_kelas'])->prefix('wali-kelas')->name('wali-kelas.')->group(function () {
    Route::get('/dashboard', [WaliKelasPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/siswa', [WaliKelasPortalController::class, 'siswa'])->name('siswa');
    Route::get('/izin', [WaliKelasPortalController::class, 'izin'])->name('izin');
    Route::post('/izin/{leave}/verify', [WaliKelasPortalController::class, 'verifyIzin'])->name('izin.verify');
    Route::get('/presensi', [WaliKelasPortalController::class, 'presensi'])->name('presensi');
});

// Halaman Profil Pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
