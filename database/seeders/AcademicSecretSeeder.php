<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicSecretSeeder extends Seeder
{
    public function run(): void
    {
        $secrets = [
            'guru1' => [
                'nisn_nip' => '198001012005011001',
                'birth_date' => '1980-01-01',
            ],
            'guru2' => [
                'nisn_nip' => '198502022010012002',
                'birth_date' => '1985-02-02',
            ],
            'walikelas1' => [
                'nisn_nip' => '198203032008011003',
                'birth_date' => '1982-03-03',
            ],
            'walikelas' => [
                'nisn_nip' => '198203032008011003',
                'birth_date' => '1982-03-03',
            ],
            'gurubk' => [
                'nisn_nip' => '198804042012012004',
                'birth_date' => '1988-04-04',
            ],
            'ortu1' => [
                'nisn_nip' => '0061234567',
                'birth_date' => '1978-05-10',
            ],
            'siswa1' => [
                'nisn_nip' => '0061234567',
                'birth_date' => '2008-05-14',
            ],
            'siswa2' => [
                'nisn_nip' => '0061234568',
                'birth_date' => '2008-08-20',
            ],
            'siswa3' => [
                'nisn_nip' => '0061234569',
                'birth_date' => '2008-11-10',
            ],
            'waka' => [
                'nisn_nip' => '197501012000031001',
                'birth_date' => '1975-01-01',
            ],
        ];

        foreach ($secrets as $username => $data) {
            User::where('username', $username)->update($data);
        }
    }
}

