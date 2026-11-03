<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'leave_type')) {
                $table->enum('leave_type', ['Sakit', 'Izin', 'Pulang Cepat', 'Kegiatan Luar'])
                      ->default('Izin')
                      ->after('applicant_id');
            }
            if (! Schema::hasColumn('leave_requests', 'leave_time')) {
                $table->time('leave_time')
                      ->nullable()
                      ->after('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('leave_requests', 'leave_type')) {
                $columns[] = 'leave_type';
            }
            if (Schema::hasColumn('leave_requests', 'leave_time')) {
                $columns[] = 'leave_time';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
