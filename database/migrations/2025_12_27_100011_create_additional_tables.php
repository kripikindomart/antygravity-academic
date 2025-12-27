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
        // SK Mengajar
        Schema::create('sk_mengajars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('nomor', 100)->unique();
            $table->date('tanggal');
            $table->string('perihal', 300);
            $table->text('dasar_hukum')->nullable();
            $table->foreignId('penandatangan_id')->constrained('users')->cascadeOnDelete();
            $table->string('jabatan_penandatangan', 200);
            $table->string('file_path', 500)->nullable();
            $table->enum('status', ['draft', 'generated', 'signed', 'distributed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        // SK Mengajar Detail
        Schema::create('sk_mengajar_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sk_mengajar_id')->constrained('sk_mengajars')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
            $table->decimal('sks', 3, 1)->default(0);
            $table->tinyInteger('jumlah_pertemuan')->default(0);
            $table->string('keterangan', 200)->nullable();
            $table->timestamps();

            $table->unique(['sk_mengajar_id', 'dosen_id', 'jadwal_id']);
        });

        // Pengumuman
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('program_studis')->nullOnDelete();
            $table->string('judul', 300);
            $table->text('konten');
            $table->enum('kategori', ['akademik', 'umum', 'beasiswa', 'lowongan', 'kegiatan', 'lainnya'])->default('umum');
            $table->enum('prioritas', ['normal', 'penting', 'urgent'])->default('normal');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('attachment', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'is_published', 'tanggal_mulai']);
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id');
            $table->string('action', 50)->comment('created, updated, deleted, etc');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
        });

        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('type', 20)->default('string')->comment('string, boolean, integer, json');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('pengumumans');
        Schema::dropIfExists('sk_mengajar_details');
        Schema::dropIfExists('sk_mengajars');
    }
};
