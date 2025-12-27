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
        // Kelas - Grouping mahasiswa per angkatan
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->string('nama', 200);
            $table->string('kode', 50)->nullable();

            // Online/Offline settings
            $table->tinyInteger('persen_online')->default(0);
            $table->tinyInteger('persen_offline')->default(100);
            $table->enum('platform_online', ['zoom', 'gmeet', 'teams'])->nullable();
            $table->string('link_online', 500)->nullable();

            // Periode
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->enum('status', ['draft', 'ready', 'generated'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['semester_id', 'prodi_id']);
        });

        // Kelas - Ruangan pivot (multi-ruangan dengan prioritas)
        Schema::create('kelas_ruangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('ruangan_id')->constrained('ruangans')->cascadeOnDelete();
            $table->tinyInteger('prioritas')->default(1);
            $table->timestamps();

            $table->unique(['kelas_id', 'ruangan_id']);
        });

        // Kelas - Mahasiswa pivot
        Schema::create('kelas_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'nonaktif', 'lulus'])->default('aktif');
            $table->timestamps();

            $table->unique(['kelas_id', 'mahasiswa_id']);
        });

        // Kelas - Mata Kuliah pivot dengan settings
        Schema::create('kelas_matakuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();

            // Jadwal settings
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->tinyInteger('sesi_per_pertemuan')->default(2);

            // Custom periode (override kelas)
            $table->boolean('use_custom_periode')->default(false);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            // Total sesi & pertemuan
            $table->tinyInteger('total_sesi')->default(16);
            $table->tinyInteger('pertemuan_uts')->default(8);
            $table->tinyInteger('pertemuan_uas')->default(16);

            $table->timestamps();

            $table->unique(['kelas_id', 'mata_kuliah_id']);
        });

        // Kelas MK - Dosen (dengan range sesi)
        Schema::create('kelas_mk_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_matakuliah_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->boolean('is_koordinator')->default(false);
            $table->tinyInteger('sesi_mulai')->default(1);
            $table->tinyInteger('sesi_selesai')->default(16);
            $table->decimal('sks_diklaim', 3, 1)->default(0);
            $table->timestamps();

            $table->unique(['kelas_matakuliah_id', 'dosen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_mk_dosen');
        Schema::dropIfExists('kelas_matakuliah');
        Schema::dropIfExists('kelas_mahasiswa');
        Schema::dropIfExists('kelas_ruangan');
        Schema::dropIfExists('kelas');
    }
};
