<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ganti username 'admin' menjadi 'waka' jika ada
        DB::table('users')
            ->where('username', 'admin')
            ->update(['username' => 'waka']);

        // 2. Ganti role 4 menjadi 'guru_bk' dengan label 'Guru BK'
        DB::table('roles')
            ->where('id', 4)
            ->update([
                'name' => 'guru_bk',
                'label' => 'Guru BK',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('username', 'waka')
            ->where('role_id', 1)
            ->update(['username' => 'admin']);

        DB::table('roles')
            ->where('id', 4)
            ->update([
                'name' => 'kesiswaan',
                'label' => 'Tim Kesiswaan',
            ]);
    }
};

