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
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            // Drop FKs first to free up the index
            $table->dropForeign(['kelas_matakuliah_id']);
            $table->dropForeign(['komponen_nilai_id']);
            $table->dropForeign(['mahasiswa_id']);

            // Drop the old strict unique constraint
            $table->dropUnique('unique_kelas_komponen_mhs');

            // Add new composite unique constraint including dosen_id
            $table->unique(['kelas_matakuliah_id', 'komponen_nilai_id', 'mahasiswa_id', 'dosen_id'], 'unique_team_teaching_grades');

            // Restore FKs
            $table->foreign('kelas_matakuliah_id')->references('id')->on('kelas_matakuliah')->cascadeOnDelete();
            $table->foreign('komponen_nilai_id')->references('id')->on('komponen_nilais')->cascadeOnDelete();
            $table->foreign('mahasiswa_id')->references('id')->on('mahasiswas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            // Drop FKs
            $table->dropForeign(['kelas_matakuliah_id']);
            $table->dropForeign(['komponen_nilai_id']);
            $table->dropForeign(['mahasiswa_id']);

            $table->dropUnique('unique_team_teaching_grades');

            // Revert to strict unique constraint
            $table->unique(['kelas_matakuliah_id', 'komponen_nilai_id', 'mahasiswa_id'], 'unique_kelas_komponen_mhs');

            // Restore FKs
            $table->foreign('kelas_matakuliah_id')->references('id')->on('kelas_matakuliah')->cascadeOnDelete();
            $table->foreign('komponen_nilai_id')->references('id')->on('komponen_nilais')->cascadeOnDelete();
            $table->foreign('mahasiswa_id')->references('id')->on('mahasiswas')->cascadeOnDelete();
        });
    }
};
