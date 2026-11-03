<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\JournalReminderService;
use App\Services\ScheduleImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WakaDashboardController extends Controller
{
    /**
     * Tampilan utama Dashboard Waka Kurikulum (Resource Lengkap)
     */
    public function index(): View
    {
        $config = SystemConfiguration::current();
        $departments = Department::withCount('classes')->orderBy('department_code')->get();
        $classes = SchoolClass::with('department')->orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();
        $roles = Role::all();
        $users = User::with(['role', 'schoolClass', 'parent', 'children'])->latest()->get();

        // Seluruh jadwal sekolah untuk master jadwal
        $schedules = Schedule::with(['teacher', 'schoolClass.department', 'room'])
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time')
            ->get();

        // Seluruh jurnal mengajar untuk monitoring waka
        $journals = Journal::with(['schedule.teacher', 'schedule.schoolClass', 'schedule.room', 'attendances.student'])
            ->latest()
            ->get();

        // Daftar tenaga pendidik (Guru, Wali Kelas, Guru BK) untuk pilihan pengampu
        $teachers = User::whereHas('role', fn ($q) => $q->whereIn('role_name', ['guru', 'wali_kelas', 'guru_bk']))
            ->orderBy('fullname')
            ->get();

        // Siswa & Orang Tua untuk penautan parent_id
        $students = User::whereHas('role', fn ($q) => $q->where('role_name', 'siswa'))
            ->with(['schoolClass', 'parent'])
            ->orderBy('fullname')
            ->get();

        $parents = User::whereHas('role', fn ($q) => $q->where('role_name', 'orang_tua'))
            ->orderBy('fullname')
            ->get();

        // Rekap keterlambatan seluruh guru untuk monitoring Waka (KF69 / BR-005)
        $overdueSchedules = JournalReminderService::getOverdueSchedules();

        return view('waka.dashboard', compact(
            'config',
            'departments',
            'classes',
            'rooms',
            'roles',
            'users',
            'schedules',
            'journals',
            'teachers',
            'students',
            'parents',
            'overdueSchedules'
        ));
    }

    /**
     * Tambah Jadwal Mengajar Sekolah (Master CRUD)
     */
    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'cycle_type' => 'required|in:Putaran 1,Putaran 2',
        ], [
            'end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
        ]);

        // Algoritma Pencegahan Bentrok Waktu (Conflict Prevention)
        $conflict = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $validated['cycle_type'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->where(function ($query) use ($validated) {
                $query->where('room_id', $validated['room_id'])
                      ->orWhere('class_id', $validated['class_id'])
                      ->orWhere('teacher_id', $validated['teacher_id']);
            })
            ->first();

        if ($conflict) {
            $msg = 'Bentrok terdeteksi! ';
            if ($conflict->room_id == $validated['room_id']) {
                $msg .= "Ruang {$conflict->room->room_name} sudah dipakai oleh {$conflict->teacher->fullname} ({$conflict->schoolClass->class_name}) pada jam {$conflict->start_time}-{$conflict->end_time}.";
            } elseif ($conflict->class_id == $validated['class_id']) {
                $msg .= "Kelas {$conflict->schoolClass->class_name} sedang diajar oleh {$conflict->teacher->fullname} pada jam {$conflict->start_time}-{$conflict->end_time}.";
            } else {
                $msg .= "Guru {$conflict->teacher->fullname} sudah memiliki jadwal lain pada jam {$conflict->start_time}-{$conflict->end_time}.";
            }

            return back()->withInput()->withErrors(['conflict' => $msg]);
        }

        Schedule::create($validated);

        return back()->with('success', "Jadwal mata pelajaran {$validated['subject']} berhasil ditambahkan!");
    }

    /**
     * Hapus Jadwal Mengajar Sekolah
     */
    public function destroySchedule(Schedule $schedule): RedirectResponse
    {
        $subject = $schedule->subject;
        $teacher = $schedule->teacher?->fullname ?? 'Guru';
        $schedule->delete();

        return back()->with('success', "Jadwal {$subject} ({$teacher}) berhasil dihapus!");
    }

    /**
     * Tautkan Orang Tua ke Siswa (Student & Parent Linking)
     */
    public function linkParent(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        $student = User::findOrFail($request->student_id);
        $student->update([
            'parent_id' => $request->parent_id ?: null,
        ]);

        $parent = $request->parent_id ? User::find($request->parent_id) : null;
        $label = $parent ? "dihubungkan dengan {$parent->fullname}" : 'dilepas dari orang tua';

        return back()->with('success', "Akun siswa {$student->fullname} berhasil {$label}!");
    }

    /**
     * Tambah Master Jurusan
     */
    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments,department_code',
            'department_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ], [
            'department_code.required' => 'Kode jurusan wajib diisi.',
            'department_code.unique' => 'Kode jurusan sudah terdaftar.',
            'department_name.required' => 'Nama jurusan wajib diisi.',
        ]);

        Department::create($validated);

        return back()->with('success', "Jurusan {$validated['department_code']} ({$validated['department_name']}) berhasil ditambahkan!");
    }

    /**
     * Hapus Master Jurusan
     */
    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->classes()->exists()) {
            return back()->with('error', "Gagal menghapus! Jurusan \"{$department->department_code}\" masih digunakan oleh rombel/kelas aktif.");
        }

        $code = $department->department_code;
        $department->delete();

        return back()->with('success', "Jurusan {$code} berhasil dihapus!");
    }

    /**
     * Tambah Master Kelas / Rombel
     */
    public function storeClass(Request $request): RedirectResponse
    {
        $request->validate([
            'class_name' => 'nullable|string|max:50|unique:classes,class_name',
            'department_id' => 'nullable|exists:departments,id',
            'grade' => 'nullable|in:X,XI,XII',
            'rombel_number' => 'nullable|string|max:10',
        ]);

        $className = $request->class_name;

        // Auto-generate nama rombel jika dipilih tingkat + jurusan + nomor rombel
        if (empty($className) && $request->grade && $request->department_id) {
            $department = Department::findOrFail($request->department_id);
            $rombelNum = trim($request->rombel_number) ?: '1';
            $className = "{$request->grade} {$department->department_code} {$rombelNum}";
        }

        if (empty($className)) {
            return back()->with('error', 'Nama kelas atau kombinasi tingkat & jurusan wajib ditentukan.');
        }

        if (SchoolClass::where('class_name', $className)->exists()) {
            return back()->with('error', "Kelas \"{$className}\" sudah ada.");
        }

        SchoolClass::create([
            'class_name' => $className,
            'department_id' => $request->department_id,
            'grade' => $request->grade,
        ]);

        return back()->with('success', "Kelas {$className} berhasil ditambahkan!");
    }

    /**
     * Hapus Master Kelas
     */
    public function destroyClass(SchoolClass $class): RedirectResponse
    {
        // Proteksi Integritas Relasi: Jangan hapus jika kelas masih memiliki jadwal atau siswa
        if ($class->schedules()->exists() || $class->users()->exists()) {
            return back()->with('error', "Kelas \"{$class->class_name}\" tidak dapat dihapus karena masih memiliki jadwal aktif atau data siswa/guru terdaftar.");
        }

        $name = $class->class_name;
        $class->delete();

        return back()->with('success', "Kelas {$name} berhasil dihapus!");
    }

    /**
     * Tambah Master Ruangan
     */
    public function storeRoom(Request $request): RedirectResponse
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create([
            'room_name' => $request->room_name,
        ]);

        return back()->with('success', "Ruangan {$request->room_name} berhasil ditambahkan!");
    }

    /**
     * Hapus Master Ruangan
     */
    public function destroyRoom(Room $room): RedirectResponse
    {
        // Proteksi Integritas Relasi: Jangan hapus jika ruangan masih digunakan pada jadwal aktif
        if ($room->schedules()->exists()) {
            return back()->with('error', "Ruangan \"{$room->room_name}\" tidak dapat dihapus karena masih digunakan dalam jadwal pembelajaran aktif.");
        }

        $name = $room->room_name;
        $room->delete();

        return back()->with('success', "Ruangan {$name} berhasil dihapus!");
    }

    /**
     * Tambah Pengguna Baru
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'fullname' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:4',
            'class_id' => 'nullable|exists:classes,id',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        User::create([
            'role_id' => $request->role_id,
            'class_id' => $request->class_id,
            'parent_id' => $request->parent_id,
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', "Pengguna {$request->username} ({$request->fullname}) berhasil didaftarkan!");
    }

    /**
     * Hapus Pengguna
     */
    public function destroyUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Proteksi Integritas Relasi: Jangan hapus pengguna jika masih memiliki jadwal mengajar aktif
        if ($user->schedules()->exists()) {
            return back()->with('error', "Pengguna \"{$user->fullname}\" tidak dapat dihapus karena masih memiliki jadwal mengajar aktif.");
        }

        $name = $user->fullname;
        $user->delete();

        return back()->with('success', "Pengguna {$name} berhasil dihapus!");
    }

    /**
     * Import Jadwal Massal CSV (KF33–KF39)
     */
    public function importSchedules(Request $request, ScheduleImportService $importer): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $result = $importer->importCsv($request->file('file'));

        $successMessage = "Proses import selesai: {$result['success_count']} jadwal berhasil ditambahkan.";

        if (! empty($result['errors'])) {
            return back()
                ->with('success', $successMessage)
                ->with('import_errors', $result['errors']);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Download format template CSV untuk Import Jadwal
     */
    public function downloadTemplate(): Response
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_jadwal.csv"',
        ];

        $content = "Nama Guru,NIP,Mata Pelajaran,Nama Kelas,Nama Ruang,Hari,Jam Mulai,Jam Selesai,Siklus\n"
                 . "Budi Santoso S.Kom,198001012005011001,Pemodelan Perangkat Lunak,XII RPL 1,Lab RPL 1,Senin,07:00:00,09:30:00,Putaran 1\n"
                 . "Siti Aminah M.Kom,198502022010012002,Basis Data,XII RPL 1,Lab RPL 2,Senin,09:45:00,12:15:00,Putaran 1\n";

        return response($content, 200, $headers);
    }
}
