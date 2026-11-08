<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homeroom_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->onDelete('cascade');
            $table->string('class_name', 50);
            $table->string('academic_year', 20)->default('2026/2027');
            $table->timestamps();

            $table->unique(['user_id', 'class_name', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');
    }
};

