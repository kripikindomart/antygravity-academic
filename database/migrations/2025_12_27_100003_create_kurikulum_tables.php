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
        // Kurikulum
        Schema::create('kurikulums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->string('kode', 20)->unique();
            $table->string('nama', 200);
            $table->year('tahun');
            $table->integer('total_sks_wajib')->default(0);
            $table->integer('total_sks_pilihan')->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'is_active']);
        });

        // Kurikulum Aktif per Tahun Akademik
        Schema::create('kurikulum_aktifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademiks')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->foreignId('kurikulum_id')->constrained('kurikulums')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tahun_akademik_id', 'prodi_id']);
        });

        // Mata Kuliah
        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->string('kode', 20)->unique();
            $table->string('nama', 200);
            $table->string('nama_en', 200)->nullable()->comment('Nama dalam bahasa Inggris');
            $table->tinyInteger('sks_teori')->default(0);
            $table->tinyInteger('sks_praktik')->default(0);
            $table->tinyInteger('semester')->comment('Semester ke berapa');
            $table->enum('jenis', ['wajib', 'pilihan'])->default('wajib');
            $table->text('deskripsi')->nullable();
            $table->text('deskripsi_en')->nullable();
            $table->foreignId('prasyarat_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'semester']);
        });

        // Kurikulum - Mata Kuliah pivot
        Schema::create('kurikulum_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained('kurikulums')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->tinyInteger('semester_rekomendasi');
            $table->timestamps();

            $table->unique(['kurikulum_id', 'mata_kuliah_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kurikulum_mata_kuliah');
        Schema::dropIfExists('mata_kuliahs');
        Schema::dropIfExists('kurikulum_aktifs');
        Schema::dropIfExists('kurikulums');
    }
};
