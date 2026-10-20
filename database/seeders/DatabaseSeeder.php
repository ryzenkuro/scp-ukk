<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\User;
use App\Models\SystemConfiguration;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            'super_admin' => Role::firstOrCreate(['role_name' => 'super_admin']),
            'waka_kurikulum' => Role::firstOrCreate(['role_name' => 'waka_kurikulum']),
            'guru' => Role::firstOrCreate(['role_name' => 'guru']),
            'wali_kelas' => Role::firstOrCreate(['role_name' => 'wali_kelas']),
            'guru_bk' => Role::firstOrCreate(['role_name' => 'guru_bk']),
            'siswa' => Role::firstOrCreate(['role_name' => 'siswa']),
            'orang_tua' => Role::firstOrCreate(['role_name' => 'orang_tua']),
        ];

        // 2. Classes
        $class12Rpl1 = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 1']);
        $class12Rpl2 = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 2']);
        $class12Rpl3 = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 3']);

        // 3. Rooms
        $roomLabRpl1 = Room::firstOrCreate(['room_name' => 'Lab RPL 1']);
        $roomLabRpl2 = Room::firstOrCreate(['room_name' => 'Lab RPL 2']);
        $roomTeori1 = Room::firstOrCreate(['room_name' => 'Ruang Teori 1']);

        $defaultPassword = Hash::make('password');

        // 4. Users Master Accounts
        $muzan = User::firstOrCreate(['username' => 'Muzan'], [
            'role_id' => $roles['super_admin']->id,
            'fullname' => 'Super Administrator (Muzan)',
            'password' => $defaultPassword,
        ]);

        $waka = User::firstOrCreate(['username' => 'waka'], [
            'role_id' => $roles['waka_kurikulum']->id,
            'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
            'password' => $defaultPassword,
        ]);

        $guru1 = User::firstOrCreate(['username' => 'guru1'], [
            'role_id' => $roles['guru']->id,
            'fullname' => 'Budi Santoso, S.Kom (Guru RPL)',
            'password' => $defaultPassword,
        ]);

        $guru2 = User::firstOrCreate(['username' => 'guru2'], [
            'role_id' => $roles['guru']->id,
            'fullname' => 'Siti Aminah, M.Kom (Guru RPL)',
            'password' => $defaultPassword,
        ]);

        $walikelas1 = User::firstOrCreate(['username' => 'walikelas1'], [
            'role_id' => $roles['wali_kelas']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        // Alias walikelas untuk kompatibilitas
        User::firstOrCreate(['username' => 'walikelas'], [
            'role_id' => $roles['wali_kelas']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $guruBk = User::firstOrCreate(['username' => 'gurubk'], [
            'role_id' => $roles['guru_bk']->id,
            'fullname' => 'Nurul Fadilah, S.Psi (Guru BK)',
            'password' => $defaultPassword,
        ]);


        $ortu1 = User::firstOrCreate(['username' => 'ortu1'], [
            'role_id' => $roles['orang_tua']->id,
            'fullname' => 'Bambang Sudarmono (Orang Tua Ahmad)',
            'password' => $defaultPassword,
        ]);

        $siswa1 = User::firstOrCreate(['username' => 'siswa1'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl1->id,
            'parent_id' => $ortu1->id,
            'fullname' => 'Ahmad Fadhil (Siswa XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $siswa2 = User::firstOrCreate(['username' => 'siswa2'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl1->id,
            'fullname' => 'Annisa Rahmawati (Siswa XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $siswa3 = User::firstOrCreate(['username' => 'siswa3'], [
            'role_id' => $roles['siswa']->id,
            'class_id' => $class12Rpl2->id,
            'fullname' => 'Dimas Arya (Siswa XII RPL 2)',
            'password' => $defaultPassword,
        ]);

        // 5. System Configuration Singleton (id: 1)
        SystemConfiguration::firstOrCreate(['id' => 1], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ]);
    }
}
