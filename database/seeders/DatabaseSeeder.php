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
            'waka_kurikulum',
            'guru',
            'wali_kelas',
            'guru_bk',
            'siswa',
            'orang_tua',
        ];

        $roleModels = [];
        foreach ($roles as $r) {
            $roleModels[$r] = Role::firstOrCreate(['role_name' => $r]);
        }

        // 2. Classes
        $c1 = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 1']);
        $c2 = SchoolClass::firstOrCreate(['class_name' => 'XII RPL 2']);
        $c3 = SchoolClass::firstOrCreate(['class_name' => 'XI RPL 1']);

        // 3. Rooms
        $r1 = Room::firstOrCreate(['room_name' => 'Lab RPL 1']);
        $r2 = Room::firstOrCreate(['room_name' => 'Lab RPL 2']);
        $r3 = Room::firstOrCreate(['room_name' => 'Ruang Teori 1']);

        $defaultPassword = Hash::make('password');

        // 4. Users Master Accounts
        $waka = User::firstOrCreate(['username' => 'waka'], [
            'role_id' => $roleModels['waka_kurikulum']->id,
            'fullname' => 'Drs. H. Hartono, M.Pd (Waka Kurikulum)',
            'password' => $defaultPassword,
        ]);

        $guru1 = User::firstOrCreate(['username' => 'guru1'], [
            'role_id' => $roleModels['guru']->id,
            'fullname' => 'Budi Santoso, S.Kom (Guru RPL)',
            'password' => $defaultPassword,
        ]);

        $guru2 = User::firstOrCreate(['username' => 'guru2'], [
            'role_id' => $roleModels['guru']->id,
            'fullname' => 'Siti Aminah, M.Kom (Guru RPL)',
            'password' => $defaultPassword,
        ]);

        $wali1 = User::firstOrCreate(['username' => 'walikelas'], [
            'role_id' => $roleModels['wali_kelas']->id,
            'class_id' => $c1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        // Alias walikelas1 for rubric compatibility
        User::firstOrCreate(['username' => 'walikelas1'], [
            'role_id' => $roleModels['wali_kelas']->id,
            'class_id' => $c1->id,
            'fullname' => 'Rahmat Hidayat, S.Pd (Wali XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $bk = User::firstOrCreate(['username' => 'gurubk'], [
            'role_id' => $roleModels['guru_bk']->id,
            'fullname' => 'Nurul Fadilah, S.Psi (Guru BK)',
            'password' => $defaultPassword,
        ]);

        $ortu1 = User::firstOrCreate(['username' => 'ortu1'], [
            'role_id' => $roleModels['orang_tua']->id,
            'fullname' => 'Bambang Sudarmono (Orang Tua Ahmad)',
            'password' => $defaultPassword,
        ]);

        $siswa1 = User::firstOrCreate(['username' => 'siswa1'], [
            'role_id' => $roleModels['siswa']->id,
            'class_id' => $c1->id,
            'parent_id' => $ortu1->id,
            'fullname' => 'Ahmad Fadhil (Siswa XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $siswa2 = User::firstOrCreate(['username' => 'siswa2'], [
            'role_id' => $roleModels['siswa']->id,
            'class_id' => $c1->id,
            'fullname' => 'Annisa Rahmawati (Siswa XII RPL 1)',
            'password' => $defaultPassword,
        ]);

        $siswa3 = User::firstOrCreate(['username' => 'siswa3'], [
            'role_id' => $roleModels['siswa']->id,
            'class_id' => $c2->id,
            'fullname' => 'Dimas Arya (Siswa XII RPL 2)',
            'password' => $defaultPassword,
        ]);

        // 5. System Configuration Singleton (id: 1)
        SystemConfiguration::updateOrCreate(['id' => 1], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'updated_by' => $waka->id,
        ]);
    }
}
