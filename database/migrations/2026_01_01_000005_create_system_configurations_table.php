<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('active_cycle')->default(1);
            $table->boolean('is_scheduled_locked')->default(false);
            $table->boolean('cycle_one_locked')->default(false);
            $table->boolean('cycle_two_locked')->default(false);
            $table->string('academic_year', 30)->default('2026/2027');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};

