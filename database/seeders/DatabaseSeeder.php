<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\TimeSlot;
use App\Models\SystemConfiguration;
use App\Models\Siswa;
use App\Models\Alumni;
use App\Models\Schedule;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('smkn1bantul2026');

        // 1. Roles
        $roles = [
            1 => Role::firstOrCreate(['id' => 1], ['name' => 'waka', 'label' => 'Waka Kurikulum / Administrator']),
            2 => Role::firstOrCreate(['id' => 2], ['name' => 'guru', 'label' => 'Tenaga Pendidik / Guru']),
            3 => Role::firstOrCreate(['id' => 3], ['name' => 'siswa', 'label' => 'Siswa / Murid']),
            4 => Role::updateOrCreate(['id' => 4], ['name' => 'guru_bk', 'label' => 'Guru BK']),
            5 => Role::firstOrCreate(['id' => 5], ['name' => 'wali_kelas', 'label' => 'Wali Kelas']),
            6 => Role::firstOrCreate(['id' => 6], ['name' => 'orang_tua', 'label' => 'Orang Tua Murid']),
        ];

        // 2. School Classes
        $class10 = SchoolClass::firstOrCreate(['name' => 'X RPL 1'], ['grade' => 'X', 'jurusan' => 'RPL']);
        $class11 = SchoolClass::firstOrCreate(['name' => 'XI RPL 1'], ['grade' => 'XI', 'jurusan' => 'RPL']);
        $class12Rpl1 = SchoolClass::firstOrCreate(['name' => 'XII RPL 1'], ['grade' => 'XII', 'jurusan' => 'RPL']);
        $class12Rpl2 = SchoolClass::firstOrCreate(['name' => 'XII RPL 2'], ['grade' => 'XII', 'jurusan' => 'RPL']);

        // 3. Rooms
        $room1 = Room::firstOrCreate(['name' => 'Lab RPL 1']);
        $room2 = Room::firstOrCreate(['name' => 'Lab RPL 2']);
        $room3 = Room::firstOrCreate(['name' => 'Ruang Teori 1']);

        // 4. Master Jam Pembelajaran (TimeSlots)
        $timeSlots = [
            ['name' => 'Jam Ke-1', 'start_time' => '07:00:00', 'end_time' => '07:45:00', 'is_break' => false, 'order' => 1],
            ['name' => 'Jam Ke-2', 'start_time' => '07:45:00', 'end_time' => '08:30:00', 'is_break' => false, 'order' => 2],
            ['name' => 'Jam Ke-3', 'start_time' => '08:30:00', 'end_time' => '09:15:00', 'is_break' => false, 'order' => 3],
            ['name' => 'Istirahat Pagi', 'start_time' => '09:15:00', 'end_time' => '09:45:00', 'is_break' => true, 'order' => 4],
            ['name' => 'Jam Ke-4', 'start_time' => '09:45:00', 'end_time' => '10:30:00', 'is_break' => false, 'order' => 5],
            ['name' => 'Jam Ke-5', 'start_time' => '10:30:00', 'end_time' => '11:15:00', 'is_break' => false, 'order' => 6],
            ['name' => 'Jam Ke-6', 'start_time' => '11:15:00', 'end_time' => '12:00:00', 'is_break' => false, 'order' => 7],
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::firstOrCreate(['name' => $slot['name']], $slot);
        }

        // 5. System Configuration
        SystemConfiguration::firstOrCreate(['id' => 1], [
            'active_cycle' => 1,
            'is_scheduled_locked' => false,
            'cycle_one_locked' => false,
            'cycle_two_locked' => false,
            'academic_year' => '2026/2027',
        ]);

        // 6. User Accounts Demo
        // Waka / Admin Utama
        $wakaUser = User::updateOrCreate(
            ['username' => 'waka'],
            [
                'role_id' => 1,
                'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
                'email' => 'waka@smkn1bantul.sch.id',
                'phone' => '081234567890',
                'password' => $defaultPassword,
                'must_change_credentials' => false,
                'is_active' => true,
            ]
        );

        // Guru 1 (Pengampu RPL)
        $guru1 = User::updateOrCreate(
            ['username' => 'guru1'],
            [
                'role_id' => 2,
                'fullname' => 'Budi Santoso, S.Kom (Guru RPL)',
                'email' => 'budi@smkn1bantul.sch.id',
                'phone' => '081234567891',
                'password' => $defaultPassword,
                'must_change_credentials' => false,
                'is_active' => true,
            ]
        );

        // Guru 2 (Guru Basis Data)
        $guru2 = User::updateOrCreate(
            ['username' => 'guru2'],
            [
                'role_id' => 2,
                'fullname' => 'Siti Aminah, M.Kom (Guru Basis Data)',
                'email' => 'siti@smkn1bantul.sch.id',
                'phone' => '081234567892',
                'password' => $defaultPassword,
                'must_change_credentials' => false,
                'is_active' => true,
            ]
        );

        // 7. Siswa & Akun Siswa / Ortu Demo (Dengan alur aktivasi must_change_credentials = true)
        $studentsData = [
            [
                'nis' => '202611001',
                'name' => 'MUHYIYUDDIN',
                'kelas' => 'XII RPL 2',
                'jurusan' => 'RPL',
                'angkatan' => 2024,
                'gender' => 'L',
                'alamat' => 'Bantul, D.I. Yogyakarta',
                'phone' => '081299887711',
                'email' => 'muhyiyuddin@siswa.sch.id',
            ],
            [
                'nis' => '202611002',
                'name' => 'AHMAD ZAKI',
                'kelas' => 'XII RPL 1',
                'jurusan' => 'RPL',
                'angkatan' => 2024,
                'gender' => 'L',
                'alamat' => 'Sewon, Bantul',
                'phone' => '081299887722',
                'email' => 'zaki@siswa.sch.id',
            ],
            [
                'nis' => '202611003',
                'name' => 'CITRA LESTARI',
                'kelas' => 'XI RPL 1',
                'jurusan' => 'RPL',
                'angkatan' => 2025,
                'gender' => 'P',
                'alamat' => 'Kasihan, Bantul',
                'phone' => '081299887733',
                'email' => 'citra@siswa.sch.id',
            ],
            [
                'nis' => '202611004',
                'name' => 'DANI PRATAMA',
                'kelas' => 'X RPL 1',
                'jurusan' => 'RPL',
                'angkatan' => 2026,
                'gender' => 'L',
                'alamat' => 'Pundong, Bantul',
                'phone' => '081299887744',
                'email' => 'dani@siswa.sch.id',
            ],
        ];

        foreach ($studentsData as $item) {
            $siswa = Siswa::updateOrCreate(['nis' => $item['nis']], $item);

            // Akun Siswa (Default Username = NIS, Password = smkn1bantul2026, must_change_credentials = true)
            User::updateOrCreate(
                ['username' => $item['nis']],
                [
                    'role_id' => 3,
                    'student_id' => $siswa->id,
                    'fullname' => $siswa->name,
                    'email' => $siswa->email,
                    'phone' => $siswa->phone,
                    'password' => $defaultPassword,
                    'must_change_credentials' => true,
                    'is_active' => true,
                ]
            );

            // Akun Orang Tua (Default Username = ortu_[NIS], Password = smkn1bantul2026, must_change_credentials = true)
            User::updateOrCreate(
                ['username' => 'ortu_' . $item['nis']],
                [
                    'role_id' => 6,
                    'student_id' => $siswa->id,
                    'fullname' => 'Orang Tua ' . $siswa->name,
                    'phone' => $siswa->phone,
                    'password' => $defaultPassword,
                    'must_change_credentials' => true,
                    'is_active' => true,
                ]
            );
        }

        // 8. Data Alumni Demo (Satu Aktif, Satu Expired untuk pengujian retensi)
        Alumni::firstOrCreate(['nis' => '202510001'], [
            'name' => 'Fajar Nugroho (Alumni Aktif)',
            'jurusan' => 'RPL',
            'kelas_terakhir' => 'XII RPL 1',
            'tahun_lulus' => '2026',
            'tanggal_lulus' => '2026-05-15',
            'alamat' => 'Bantul',
            'keterangan' => 'Lulus Tingkat XII Tahun 2026 (Masa aktif sampai 2027)',
        ]);

        Alumni::firstOrCreate(['nis' => '202310088'], [
            'name' => 'Bambang Tri (Alumni Expired)',
            'jurusan' => 'RPL',
            'kelas_terakhir' => 'XII RPL 2',
            'tahun_lulus' => '2024',
            'tanggal_lulus' => '2024-05-20',
            'alamat' => 'Yogyakarta',
            'keterangan' => 'Lulus Tingkat XII Tahun 2024 (Sudah melebihi 1 tahun, status expired)',
        ]);

        // 9. Jadwal Sekolah Demo
        Schedule::firstOrCreate([
            'day_of_week' => 'Senin',
            'cycle_number' => 1,
            'subject' => 'Pemrograman Web & Mobile',
            'class_name' => 'XII RPL 2',
        ], [
            'teacher_id' => $guru1->id,
            'class_id' => $class12Rpl2->id,
            'room_id' => $room1->id,
            'room_name' => $room1->name,
            'start_time' => '07:00:00',
            'end_time' => '09:15:00',
            'start_slot_id' => 1,
            'end_slot_id' => 3,
            'academic_year' => '2026/2027',
        ]);
    }
}
