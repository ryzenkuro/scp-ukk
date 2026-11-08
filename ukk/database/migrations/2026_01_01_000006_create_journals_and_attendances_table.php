<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('topic', 255);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('siswas')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->default('hadir');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('siswas')->onDelete('cascade');
            $table->enum('type', ['sakit', 'izin'])->default('sakit');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('journals');
    }
};

