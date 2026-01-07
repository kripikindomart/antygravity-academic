<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_template_id')->constrained('survey_templates')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademiks')->cascadeOnDelete();
            $table->string('nama')->nullable(); // e.g., "EDOM Semester Ganjil 2025/2026"
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->boolean('is_mandatory')->default(false); // Wajib isi sebelum lihat nilai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_periods');
    }
};
