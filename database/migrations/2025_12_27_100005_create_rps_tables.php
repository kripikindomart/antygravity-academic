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
        // RPS - Rencana Pembelajaran Semester
        Schema::create('rps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('dosen_pengampu_id')->constrained('dosens')->cascadeOnDelete();
            $table->text('tujuan_pembelajaran')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('pustaka_utama')->nullable();
            $table->text('pustaka_pendukung')->nullable();
            $table->json('metode_pembelajaran')->nullable()->comment('Ceramah, Diskusi, Praktik, dll');
            $table->json('komponen_penilaian')->nullable()->comment('[{komponen: "UTS", bobot: 30}, ...]');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mata_kuliah_id', 'semester_id']);
        });

        // RPS Detail - Detail per pertemuan
        Schema::create('rps_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rps_id')->constrained('rps')->cascadeOnDelete();
            $table->tinyInteger('pertemuan_ke');
            $table->foreignId('sub_cpmk_id')->nullable()->constrained('sub_cpmks')->nullOnDelete();
            $table->text('materi');
            $table->string('metode', 200)->nullable();
            $table->text('indikator_ketercapaian')->nullable();
            $table->decimal('bobot_penilaian', 5, 2)->default(0);
            $table->text('referensi')->nullable();
            $table->text('media_pembelajaran')->nullable();
            $table->integer('estimasi_waktu')->nullable()->comment('dalam menit');
            $table->timestamps();

            $table->unique(['rps_id', 'pertemuan_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rps_details');
        Schema::dropIfExists('rps');
    }
};
