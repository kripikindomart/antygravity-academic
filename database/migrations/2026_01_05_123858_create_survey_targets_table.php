<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_period_id')->constrained('survey_periods')->cascadeOnDelete();
            $table->foreignId('kelas_matakuliah_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosens')->cascadeOnDelete();
            $table->timestamps();

            // Unique constraint: one target per class-dosen combination per period
            $table->unique(['survey_period_id', 'kelas_matakuliah_id', 'dosen_id'], 'unique_survey_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_targets');
    }
};
