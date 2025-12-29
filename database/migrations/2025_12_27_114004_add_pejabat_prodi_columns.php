<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Create separate table for jabatan struktural (kaprodi, sekprodi, etc)
     */
    public function up(): void
    {
        Schema::create('jabatan_strukturals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_studi_id')->constrained('program_studis')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('dosens')->onDelete('cascade');
            $table->enum('jabatan', ['kaprodi', 'sekprodi', 'koordinator']);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('sk_nomor')->nullable(); // Nomor SK pengangkatan
            $table->string('ttd_path')->nullable(); // Path tanda tangan digital
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: one active jabatan per prodi per type
            $table->unique(['program_studi_id', 'jabatan', 'is_active'], 'unique_active_jabatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatan_strukturals');
    }
};
