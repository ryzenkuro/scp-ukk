<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nisn_nip')) {
                $table->string('nisn_nip', 30)->nullable()->after('fullname');
            }
            if (! Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('nisn_nip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'birth_date')) {
                $columns[] = 'birth_date';
            }
            if (Schema::hasColumn('users', 'nisn_nip')) {
                $columns[] = 'nisn_nip';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

