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
        // Nilai per komponen
        Schema::create('nilais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->string('komponen', 50)->comment('UTS, UAS, Tugas-1, dll');
            $table->decimal('nilai', 5, 2)->default(0);
            $table->decimal('bobot', 5, 2)->default(0)->comment('Bobot dalam persen');
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jadwal_id', 'mahasiswa_id', 'komponen']);
        });

        // Nilai Akhir
        Schema::create('nilai_akhirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->decimal('nilai_angka', 5, 2)->default(0);
            $table->char('nilai_huruf', 2)->nullable()->comment('A, B+, B, C+, C, D, E');
            $table->decimal('nilai_mutu', 3, 2)->default(0)->comment('4.00, 3.50, dll');
            $table->enum('status', ['lulus', 'tidak_lulus', 'belum_final'])->default('belum_final');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jadwal_id', 'mahasiswa_id']);
        });

        // KHS Cache (untuk optimasi query)
        Schema::create('khs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->integer('total_sks')->default(0);
            $table->decimal('total_mutu', 8, 2)->default(0);
            $table->decimal('ips', 3, 2)->default(0)->comment('Indeks Prestasi Semester');
            $table->decimal('ipk', 3, 2)->default(0)->comment('Indeks Prestasi Komulatif');
            $table->integer('sks_lulus')->default(0);
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khs');
        Schema::dropIfExists('nilai_akhirs');
        Schema::dropIfExists('nilais');
    }
};
