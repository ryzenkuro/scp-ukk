<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('name');
            $table->string('kelas', 30);
            $table->string('jurusan', 50)->default('RPL');
            $table->integer('angkatan')->default(2026);
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('alamat')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['aktif', 'lulus', 'mutasi', 'keluar'])->default('aktif');
            $table->string('tahun_lulus', 10)->nullable();
            $table->date('tanggal_lulus')->nullable();
            $table->timestamps();
        });

        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->string('nis', 20);
            $table->string('name');
            $table->string('jurusan', 50)->nullable();
            $table->string('kelas_terakhir', 30)->nullable();
            $table->string('tahun_lulus', 10);
            $table->date('tanggal_lulus');
            $table->string('alamat')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email')->nullable();
            $table->string('keterangan', 255)->default('Lulus Tingkat XII');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnis');
        Schema::dropIfExists('siswas');
    }
};

