<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_target_id')->constrained('survey_targets')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete(); // NOT ANONYMOUS - EDOM
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // One response per mahasiswa per target
            $table->unique(['survey_target_id', 'mahasiswa_id'], 'unique_survey_response');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
