<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Contoh: "Jam Ke-1", "Istirahat Pagi", "Jam Ke-2"
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week', 20); // Senin, Selasa, dst.
            $table->unsignedTinyInteger('cycle_number')->default(1); // Putaran 1 atau Putaran 2
            $table->string('subject', 100);
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('class_name', 50);
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('set null');
            $table->string('room_name', 50)->nullable();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('start_slot_id')->nullable();
            $table->unsignedBigInteger('end_slot_id')->nullable();
            $table->string('academic_year', 20)->default('2026/2027');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('time_slots');
    }
};

