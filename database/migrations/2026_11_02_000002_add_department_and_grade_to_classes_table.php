<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('class_name')->constrained('departments')->nullOnDelete();
            $table->enum('grade', ['X', 'XI', 'XII'])->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'grade']);
        });
    }
};

