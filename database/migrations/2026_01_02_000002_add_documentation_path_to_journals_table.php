<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (! Schema::hasColumn('journals', 'documentation_path')) {
                $table->string('documentation_path')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'documentation_path')) {
                $table->dropColumn('documentation_path');
            }
        });
    }
};
