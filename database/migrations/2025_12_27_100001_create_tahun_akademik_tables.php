<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tahun Akademik - Central entity for all academic data
        Schema::create('tahun_akademiks', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('e.g., 2024/2025');
            $table->string('nama', 100)->comment('Tahun Akademik 2024/2025');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Semester - Linked to Tahun Akademik
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademiks')->cascadeOnDelete();
            $table->string('kode', 10)->unique()->comment('e.g., 20241 = Ganjil 2024');
            $table->enum('tipe', ['ganjil', 'genap', 'pendek'])->default('ganjil');
            $table->string('nama', 100);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_mulai_krs')->nullable();
            $table->date('tanggal_selesai_krs')->nullable();
            $table->date('tanggal_uts')->nullable();
            $table->date('tanggal_uas')->nullable();
            $table->date('tanggal_input_nilai')->nullable();
            $table->date('tanggal_deadline_nilai')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tahun_akademik_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('tahun_akademiks');
    }
};
