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
        // KRS - Kartu Rencana Studi
        Schema::create('krs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'batal', 'lulus'])->default('aktif');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mahasiswa_id', 'jadwal_id']);
            $table->index(['semester_id', 'mahasiswa_id']);
        });

        // Absensi - linked to jadwal_pertemuan
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pertemuan_id')->constrained('jadwal_pertemuans')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir');
            $table->text('keterangan')->nullable();
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jadwal_pertemuan_id', 'mahasiswa_id']);
        });

        // Jurnal Perkuliahan - linked to jadwal_pertemuan
        Schema::create('jurnals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pertemuan_id')->constrained('jadwal_pertemuans')->cascadeOnDelete();
            $table->text('materi')->nullable();
            $table->text('aktivitas')->nullable();
            $table->text('capaian')->nullable();
            $table->foreignId('sub_cpmk_id')->nullable()->constrained('sub_cpmks')->nullOnDelete()->comment('Sub-CPMK yang dicapai');
            $table->integer('jumlah_hadir')->default(0);
            $table->integer('jumlah_izin')->default(0);
            $table->integer('jumlah_sakit')->default(0);
            $table->integer('jumlah_alpha')->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->string('bukti_perkuliahan')->nullable()->comment('Path foto');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('jadwal_pertemuan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnals');
        Schema::dropIfExists('absensis');
        Schema::dropIfExists('krs');
    }
};
