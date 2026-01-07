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
        Schema::table('survey_responses', function (Blueprint $table) {
            // Drop old relation if exists
            if (Schema::hasColumn('survey_responses', 'survey_target_id')) {
                $table->dropForeign(['survey_target_id']);
                $table->dropColumn('survey_target_id');
            }

            // Add new relations
            $table->foreignId('survey_period_id')->after('id')->constrained('survey_periods')->cascadeOnDelete();
            $table->foreignId('dosen_id')->after('mahasiswa_id')->constrained('dosens')->cascadeOnDelete();
            $table->foreignId('kelas_matakuliah_id')->after('dosen_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['survey_period_id']);
            $table->dropForeign(['dosen_id']);
            $table->dropForeign(['kelas_matakuliah_id']);
            $table->dropColumn(['survey_period_id', 'dosen_id', 'kelas_matakuliah_id']);

            // Re-add target (nullable because data is lost)
            $table->foreignId('survey_target_id')->nullable()->constrained('survey_targets')->cascadeOnDelete();
        });
    }
};
