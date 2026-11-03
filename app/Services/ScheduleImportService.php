<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ScheduleImportService
{
    /**
     * Mengimpor data jadwal pembelajaran massal dari file CSV.
     * Menggunakan native PHP fgetcsv, validasi bentrok 3D (BR-001), dan auto-create akun guru (KF35).
     *
     * Format Kolom CSV:
     * [0] Nama Guru
     * [1] NIP / Username
     * [2] Mata Pelajaran
     * [3] Nama Kelas
     * [4] Nama Ruang
     * [5] Hari (Senin, Selasa, dll.)
     * [6] Jam Mulai (e.g. 07:00 / 07:00:00)
     * [7] Jam Selesai (e.g. 09:30 / 09:30:00)
     * [8] Siklus (Putaran 1 / Putaran 2)
     *
     * @param UploadedFile|string $file
     * @return array{success_count: int, errors: array<string>}
     */
    public function importCsv(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($path) || ! is_readable($path)) {
            return [
                'success_count' => 0,
                'errors' => ['File CSV tidak dapat dibaca atau tidak ditemukan.'],
            ];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [
                'success_count' => 0,
                'errors' => ['Gagal membuka file CSV.'],
            ];
        }

        // Baca dan lewati baris header
        $header = fgetcsv($handle, 1000, ',');

        $successCount = 0;
        $errors = [];
        $rowNumber = 1;

        $guruRole = Role::where('role_name', 'guru')->first();

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;

            // Abaikan baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            if (count($row) < 9) {
                $errors[] = "Baris {$rowNumber}: Format kolom tidak lengkap (dibutuhkan 9 kolom).";
                continue;
            }

            [$teacherName, $nip, $subject, $className, $roomName, $day, $startTime, $endTime, $cycle] = array_map('trim', $row);

            // Validasi kelengkapan data esensial
            if (! $teacherName || ! $subject || ! $className || ! $roomName || ! $day || ! $startTime || ! $endTime || ! $cycle) {
                $errors[] = "Baris {$rowNumber}: Terdapat kolom wajib yang kosong.";
                continue;
            }

            // Normalisasi format waktu (e.g. 07:00 -> 07:00:00)
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }
            if (strlen($endTime) === 5) {
                $endTime .= ':00';
            }

            // 1. Cari atau Buat Akun Guru Otomatis jika belum ada (KF35)
            $username = ! empty($nip) ? $nip : strtolower(str_replace(' ', '', $teacherName));
            $teacher = User::firstOrCreate(
                ['username' => $username],
                [
                    'fullname' => $teacherName,
                    'password' => Hash::make('password'),
                    'role_id' => $guruRole?->id,
                ]
            );

            // 2. Cari ID Kelas & Ruang
            $class = SchoolClass::where('class_name', $className)->first();
            $room = Room::where('room_name', $roomName)->first();

            if (! $class || ! $room) {
                $missing = [];
                if (! $class) $missing[] = "Kelas '{$className}'";
                if (! $room) $missing[] = "Ruang '{$roomName}'";
                $errors[] = "Baris {$rowNumber}: " . implode(' dan ', $missing) . " tidak ditemukan di sistem.";
                continue;
            }

            // 3. Validasi Irisan Bentrok 3 Dimensi (BR-001): (start < new_end) AND (end > new_start)
            $conflict = Schedule::where('cycle_type', $cycle)
                ->where('day_of_week', $day)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->where(function ($q) use ($room, $class, $teacher) {
                    $q->where('room_id', $room->id)
                      ->orWhere('class_id', $class->id)
                      ->orWhere('teacher_id', $teacher->id);
                })->exists();

            if ($conflict) {
                $errors[] = "Baris {$rowNumber}: Jadwal bentrok untuk guru {$teacherName} di {$roomName} ({$day}, {$startTime}-{$endTime}, {$cycle}).";
                continue;
            }

            // 4. Simpan Jadwal Pembelajaran
            Schedule::create([
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'room_id' => $room->id,
                'subject' => $subject,
                'day_of_week' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'cycle_type' => $cycle,
            ]);

            $successCount++;
        }

        fclose($handle);

        return [
            'success_count' => $successCount,
            'errors' => $errors,
        ];
    }
}
