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
        // Events / Kegiatan
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('program_studis')->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->string('judul', 300);
            $table->enum('kategori', [
                'seminar_nasional',
                'seminar_internasional',
                'workshop',
                'webinar',
                'pelatihan',
                'yudisium',
                'wisuda',
                'rapat_dosen',
                'dies_natalis',
                'lainnya'
            ])->default('lainnya');
            $table->text('deskripsi')->nullable();
            $table->datetime('tanggal_mulai');
            $table->datetime('tanggal_selesai')->nullable();
            $table->string('lokasi', 300)->nullable();
            $table->string('link_online', 500)->nullable()->comment('Zoom, Google Meet, dll');
            $table->integer('kuota')->nullable();
            $table->boolean('require_registration')->default(false);
            $table->date('deadline_registrasi')->nullable();
            $table->string('poster')->nullable();
            $table->enum('status', ['draft', 'published', 'ongoing', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prodi_id', 'status']);
            $table->index(['semester_id', 'kategori']);
        });

        // Event Registration
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama', 200)->nullable()->comment('Untuk peserta eksternal');
            $table->string('email', 200)->nullable();
            $table->string('instansi', 200)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('qr_code', 100)->unique()->nullable();
            $table->enum('status', ['registered', 'confirmed', 'attended', 'cancelled'])->default('registered');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'user_id']);
        });

        // Event Attendance
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->datetime('check_in')->nullable();
            $table->datetime('check_out')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // Event Certificates
        Schema::create('event_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('nomor', 100)->unique();
            $table->string('template', 100)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Event Gallery
        Schema::create('event_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->enum('tipe', ['foto', 'video'])->default('foto');
            $table->string('caption', 500)->nullable();
            $table->integer('urutan')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_galleries');
        Schema::dropIfExists('event_certificates');
        Schema::dropIfExists('event_attendances');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};
