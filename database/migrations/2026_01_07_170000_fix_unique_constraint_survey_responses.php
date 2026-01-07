<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Drop the Foreign Key that relies on the index
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['mahasiswa_id']);
        });

        // 2. Drop the conflicting Unique Index
        Schema::table('survey_responses', function (Blueprint $table) {
            try {
                $table->dropUnique('unique_survey_response');
            } catch (\Exception $e) {
                // Index might not exist or named differently, continue
            }
        });

        // 3. Restore Foreign Key and Add New Correct Unique Index
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('mahasiswas')
                ->cascadeOnDelete();

            $table->unique(['survey_period_id', 'mahasiswa_id', 'dosen_id', 'kelas_matakuliah_id'], 'unique_survey_response_v2');
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique('unique_survey_response_v2');
        });
    }
};
