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
        // Survei Template
        Schema::create('surveis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('nama', 200);
            $table->text('deskripsi')->nullable();
            $table->enum('tipe', ['edom', 'kepuasan', 'tracer_study', 'lainnya'])->default('edom');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Survei Pertanyaan
        Schema::create('survei_pertanyaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_id')->constrained('surveis')->cascadeOnDelete();
            $table->text('pertanyaan');
            $table->enum('tipe', ['rating', 'text', 'multiple_choice', 'checkbox'])->default('rating');
            $table->json('opsi')->nullable()->comment('Opsi untuk multiple_choice/checkbox');
            $table->integer('urutan')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // Survei Response
        Schema::create('survei_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_id')->constrained('surveis')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->nullable()->constrained('mahasiswas')->nullOnDelete()->comment('Null jika anonim');
            $table->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete()->comment('Dosen yang dinilai');
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->nullOnDelete();
            $table->json('responses')->comment('[{pertanyaan_id: 1, jawaban: 5}, ...]');
            $table->text('saran')->nullable();
            $table->timestamps();

            // Untuk mencegah double submit
            $table->unique(['survei_id', 'mahasiswa_id', 'dosen_id', 'jadwal_id'], 'unique_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survei_responses');
        Schema::dropIfExists('survei_pertanyaans');
        Schema::dropIfExists('surveis');
    }
};
