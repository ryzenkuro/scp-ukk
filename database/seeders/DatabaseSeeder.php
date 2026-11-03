<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Department;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\User;
use App\Models\SystemConfiguration;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles (6 Roles: Waka Kurikulum sebagai Administrator Tunggal)
        $roles = [
            'waka_kurikulum' => Role::firstOrCreate(['role_name' => 'waka_kurikulum']),
            'guru' => Role::firstOrCreate(['role_name' => 'guru']),
            'wali_kelas' => Role::firstOrCreate(['role_name' => 'wali_kelas']),
            'guru_bk' => Role::firstOrCreate(['role_name' => 'guru_bk']),
            'siswa' => Role::firstOrCreate(['role_name' => 'siswa']),
            'orang_tua' => Role::firstOrCreate(['role_name' => 'orang_tua']),
        ];

        // 2. Departments (Master Jurusan SMK N 1 Bantul)
        $deptRpl = Department::firstOrCreate(['department_code' => 'RPL'], [
            'department_name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Konsentrasi Keahlian Pengembangan Perangkat Lunak dan Gim',
        ]);
        $deptTkj = Department::firstOrCreate(['department_code' => 'TKJ'], [
            'department_name' => 'Teknik Komputer & Jaringan',
            'description' => 'Konsentrasi Keahlian Teknik Komputer dan Jaringan Telekomunikasi',
        ]);
        $deptAkl = Department::firstOrCreate(['department_code' => 'AKL'], [
            'department_name' => 'Akuntansi & Keuangan Lembaga',
            'description' => 'Konsentrasi Keahlian Akuntansi dan Keuangan Lembaga',
        ]);
        $deptDkv = Department::firstOrCreate(['department_code' => 'DKV'], [
            'department_name' => 'Desain Komunikasi Visual',
            'description' => 'Konsentrasi Keahlian Desain Grafis dan Multimedia Kreatif',
        ]);
        $deptBdp = Department::firstOrCreate(['department_code' => 'BDP'], [
            'department_name' => 'Bisnis Daring & Pemasaran',
            'description' => 'Konsentrasi Keahlian Pemasaran Digital dan Retail Modern',
        ]);

        // 3. Classes (Rombel)
        $class12Rpl1 = SchoolClass::firstOrCreate(
            ['class_name' => 'XII RPL 1'],
            ['department_id' => $deptRpl->id, 'grade' => 'XII']
        );
        $class12Rpl1->update(['department_id' => $deptRpl->id, 'grade' => 'XII']);

        $class12Rpl2 = SchoolClass::firstOrCreate(
            ['class_name' => 'XII RPL 2'],
            ['department_id' => $deptRpl->id, 'grade' => 'XII']
        );
        $class12Rpl2->update(['department_id' => $deptRpl->id, 'grade' => 'XII']);

        $class12Rpl3 = SchoolClass::firstOrCreate(
            ['class_name' => 'XII RPL 3'],
            ['department_id' => $deptRpl->id, 'grade' => 'XII']
        );
        $class12Rpl3->update(['department_id' => $deptRpl->id, 'grade' => 'XII']);

        // Rombel tambahan untuk demo filter X, XI, XII & Jurusan
        SchoolClass::firstOrCreate(
            ['class_name' => 'XI RPL 1'],
            ['department_id' => $deptRpl->id, 'grade' => 'XI']
        );
        SchoolClass::firstOrCreate(
            ['class_name' => 'X RPL 1'],
            ['department_id' => $deptRpl->id, 'grade' => 'X']
        );
        SchoolClass::firstOrCreate(
            ['class_name' => 'XII TKJ 1'],
            ['department_id' => $deptTkj->id, 'grade' => 'XII']
        );
        SchoolClass::firstOrCreate(
            ['class_name' => 'XI TKJ 1'],
            ['department_id' => $deptTkj->id, 'grade' => 'XI']
        );
        SchoolClass::firstOrCreate(
            ['class_name' => 'X TKJ 1'],
            ['department_id' => $deptTkj->id, 'grade' => 'X']
        );
        SchoolClass::firstOrCreate(
            ['class_name' => 'XII AKL 1'],
            ['department_id' => $deptAkl->id, 'grade' => 'XII']
        );

        // 3. Rooms
        $roomLabRpl1 = Room::firstOrCreate(['room_name' => 'Lab RPL 1']);
        $roomLabRpl2 = Room::firstOrCreate(['room_name' => 'Lab RPL 2']);
        $roomTeori1 = Room::firstOrCreate(['room_name' => 'Ruang Teori 1']);

        $defaultPassword = Hash::make('password');

        // 4. Users Master Accounts
        $waka = User::firstOrCreate(['username' => 'waka'], [
            'role_id' => $roles['waka_kurikulum']->id,
            'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
            'password' => $defaultPassword,
            'nisn_nip' => '197501012000031001',
            'birth_date' => '1975-01-01',
        ]);

        $guru1 = User::firstOrCreate(['username' => 'guru1'], [
            'role_id' => $roles['guru']->id,
            'fullname' => 'Budi Santoso, S.Kom (Guru RPL)',
            'password' => $defaultPassword,
            'nisn_nip' => '198001012005011001',
            'birth_date' => '1980-01-01',
        ]);

        $guru2 = User::firstOrCreate(['username' => 'guru2'], [
            'role_id' => $roles['guru']->id,
            'fullname' => 'Siti Aminah, M.Kom (Guru RPL)',
            'password' => $defaultPassword,
            'nisn_nip' => '198502022010012002',
            'birth_date' => '1985-02-02',
        ]);

        $walikelas1 = User::firstOrCreate(['username' => 'walikelas1'], [
            'role_id' => $roles['wali_kelas']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
            'nisn_nip' => '198203032008011003',
            'birth_date' => '1982-03-03',
        ]);

        // Alias walikelas untuk kompatibilitas
        User::firstOrCreate(['username' => 'walikelas'], [
            'role_id' => $roles['wali_kelas']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
            'nisn_nip' => '198203032008011003',
            'birth_date' => '1982-03-03',
        ]);

        $guruBk = User::firstOrCreate(['username' => 'gurubk'], [
            'role_id' => $roles['guru_bk']->id,
            'fullname' => 'Nurul Fadilah, S.Psi (Guru BK)',
            'password' => $defaultPassword,
            'nisn_nip' => '198804042012012004',
            'birth_date' => '1988-04-04',
        ]);


        $ortu1 = User::firstOrCreate(['username' => 'ortu1'], [
            'role_id' => $roles['orang_tua']->id,
            'fullname' => 'Bambang Sudarmono (Orang Tua Ahmad)',
            'password' => $defaultPassword,
            'nisn_nip' => '0061234567',
            'birth_date' => '1978-05-10',
        ]);

        $siswa1 = User::firstOrCreate(['username' => 'siswa1'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl1->id,
            'parent_id' => $ortu1->id,
            'fullname' => 'Ahmad Fadhil (Siswa XII RPL 1)',
            'password' => $defaultPassword,
            'nisn_nip' => '0061234567',
            'birth_date' => '2008-05-14',
        ]);

        $siswa2 = User::firstOrCreate(['username' => 'siswa2'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Annisa Rahmawati (Siswa XII RPL 1)',
            'password' => $defaultPassword,
            'nisn_nip' => '0061234568',
            'birth_date' => '2008-08-20',
        ]);

        $siswa3 = User::firstOrCreate(['username' => 'siswa3'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl2->id,
            'fullname' => 'Dimas Arya (Siswa XII RPL 2)',
            'password' => $defaultPassword,
            'nisn_nip' => '0061234569',
            'birth_date' => '2008-11-10',
        ]);

        // 5. System Configuration Singleton (id: 1)
        SystemConfiguration::firstOrCreate(['id' => 1], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ]);
    }
}
