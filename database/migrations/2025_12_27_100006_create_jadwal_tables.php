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
        // Ruangan
        Schema::create('ruangans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->string('gedung', 100)->nullable();
            $table->string('lantai', 20)->nullable();
            $table->integer('kapasitas')->default(0);
            $table->json('fasilitas')->nullable()->comment('Proyektor, AC, dll');
            $table->enum('tipe', ['kelas', 'lab', 'aula', 'ruang_rapat', 'lainnya'])->default('kelas');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Jadwal Kuliah
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->string('kelas', 10)->default('A');
            $table->foreignId('ruangan_id')->nullable()->constrained('ruangans')->nullOnDelete();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');

            // Session settings
            $table->tinyInteger('jumlah_sesi')->default(2)->comment('Jumlah sesi per pertemuan');
            $table->integer('durasi_sesi')->default(120)->comment('Durasi per sesi dalam menit (default 2 jam)');
            $table->tinyInteger('total_pertemuan')->default(16)->comment('Total pertemuan termasuk UTS/UAS');
            $table->tinyInteger('pertemuan_uts')->default(8)->comment('Pertemuan ke berapa UTS');
            $table->tinyInteger('pertemuan_uas')->default(16)->comment('Pertemuan ke berapa UAS');

            $table->integer('kuota')->default(30);
            $table->integer('jumlah_peserta')->default(0);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['semester_id', 'hari']);
            $table->index(['ruangan_id', 'hari', 'jam_mulai', 'jam_selesai']);
        });

        // Jadwal Dosen - Team Teaching dengan range pertemuan
        Schema::create('jadwal_dosens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->boolean('is_koordinator')->default(false);

            // Range pertemuan untuk dosen ini
            $table->tinyInteger('pertemuan_mulai')->default(1)->comment('Mulai dari pertemuan ke');
            $table->tinyInteger('pertemuan_selesai')->default(16)->comment('Sampai pertemuan ke');

            // Perhitungan SKS
            $table->decimal('sks_diklaim', 3, 1)->default(0)->comment('SKS yang diklaim dosen');
            $table->integer('jumlah_pertemuan')->default(0)->comment('Jumlah pertemuan (exclude UTS/UAS)');

            $table->timestamps();

            $table->unique(['jadwal_id', 'dosen_id']);
        });

        // Detail Pertemuan - untuk tracking status tiap pertemuan
        Schema::create('jadwal_pertemuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->tinyInteger('pertemuan_ke');
            $table->date('tanggal')->nullable();
            $table->enum('tipe', ['kuliah', 'uts', 'uas', 'libur', 'pengganti'])->default('kuliah');
            $table->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete()->comment('Dosen yang mengajar di pertemuan ini');
            $table->enum('status', ['terjadwal', 'berlangsung', 'selesai', 'batal', 'diganti'])->default('terjadwal');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['jadwal_id', 'pertemuan_ke']);
        });

        // Perubahan Jadwal
        Schema::create('jadwal_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->foreignId('jadwal_pertemuan_id')->nullable()->constrained('jadwal_pertemuans')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal_asli');
            $table->date('tanggal_baru')->nullable();
            $table->time('jam_mulai_baru')->nullable();
            $table->time('jam_selesai_baru')->nullable();
            $table->foreignId('ruangan_baru_id')->nullable()->constrained('ruangans')->nullOnDelete();
            $table->text('alasan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_changes');
        Schema::dropIfExists('jadwal_pertemuans');
        Schema::dropIfExists('jadwal_dosens');
        Schema::dropIfExists('jadwals');
        Schema::dropIfExists('ruangans');
    }
};
