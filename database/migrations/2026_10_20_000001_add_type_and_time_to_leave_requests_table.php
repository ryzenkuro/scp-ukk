<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('leave_type', ['Sakit', 'Izin', 'Pulang Cepat', 'Kegiatan Luar'])
                  ->default('Izin')
                  ->after('applicant_id');
            $table->time('leave_time')
                  ->nullable()
                  ->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_type', 'leave_time']);
        });
    }
};
