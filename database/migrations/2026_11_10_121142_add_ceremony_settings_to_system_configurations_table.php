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
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->boolean('ceremony_cycle_1')->default(false)->after('active_cycle');
            $table->boolean('ceremony_cycle_2')->default(false)->after('ceremony_cycle_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['ceremony_cycle_1', 'ceremony_cycle_2']);
        });
    }
};
