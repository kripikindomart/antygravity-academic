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
        // Program Studi
        Schema::create('program_studis', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('MPI, MM, etc');
            $table->string('nama', 200);
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2', 'S3'])->default('S2');
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->text('tujuan')->nullable();
            $table->string('akreditasi', 50)->nullable()->comment('A, B, C, Unggul, Baik Sekali');
            $table->string('no_sk_akreditasi', 100)->nullable();
            $table->date('tanggal_akreditasi')->nullable();
            $table->date('masa_berlaku_akreditasi')->nullable();
            $table->string('sertifikat_akreditasi')->nullable()->comment('File path');
            $table->string('email', 100)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('website', 200)->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Dosen - Entitas terpisah, user_id nullable untuk bulk create user
        Schema::create('dosens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Null = belum punya akun');
            $table->foreignId('prodi_id')->nullable()->constrained('program_studis')->nullOnDelete()->comment('Homebase prodi');
            $table->string('nip', 30)->nullable()->unique();
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('nama', 200);
            $table->string('gelar_depan', 50)->nullable();
            $table->string('gelar_belakang', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('jabatan_fungsional', 100)->nullable()->comment('Asisten Ahli, Lektor, Lektor Kepala, Guru Besar');
            $table->string('jabatan_struktural', 100)->nullable();
            $table->string('pangkat_golongan', 50)->nullable();
            $table->string('bidang_keahlian', 200)->nullable();
            $table->text('pendidikan_terakhir')->nullable();
            $table->string('foto', 500)->nullable();
            $table->boolean('is_dosen_luar')->default(false);
            $table->enum('status', ['aktif', 'non_aktif', 'pensiun', 'cuti'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'status']);
        });

        // Mahasiswa - Entitas terpisah, user_id nullable untuk bulk create user
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Null = belum punya akun');
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_masuk_id')->constrained('tahun_akademiks');
            $table->string('nim', 20)->unique();
            $table->string('nama', 200);
            $table->string('email', 100)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('angkatan', 4);
            $table->enum('status', ['aktif', 'cuti', 'non_aktif', 'lulus', 'do', 'mengundurkan_diri'])->default('aktif');
            $table->string('asal_pt', 200)->nullable()->comment('Asal Perguruan Tinggi S1');
            $table->string('asal_prodi', 200)->nullable();
            $table->decimal('ipk_s1', 3, 2)->nullable();
            $table->string('foto', 500)->nullable();
            $table->foreignId('dosen_pembimbing_1_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('dosen_pembimbing_2_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_lulus')->nullable();
            $table->string('no_ijazah', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'status']);
            $table->index(['tahun_akademik_masuk_id', 'angkatan']);
        });

        // Prodi-Dosen pivot (untuk dosen yang mengajar di multi-prodi)
        Schema::create('prodi_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->boolean('is_homebase')->default(false);
            $table->timestamps();

            $table->unique(['prodi_id', 'dosen_id']);
        });

        // Update program_studis untuk kaprodi dan sekretaris (gunakan dosen_id)
        Schema::table('program_studis', function (Blueprint $table) {
            $table->foreignId('kaprodi_id')->nullable()->after('logo');
            $table->foreignId('sekretaris_id')->nullable()->after('kaprodi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_studis', function (Blueprint $table) {
            $table->dropColumn(['kaprodi_id', 'sekretaris_id']);
        });
        Schema::dropIfExists('prodi_dosen');
        Schema::dropIfExists('mahasiswas');
        Schema::dropIfExists('dosens');
        Schema::dropIfExists('program_studis');
    }
};
