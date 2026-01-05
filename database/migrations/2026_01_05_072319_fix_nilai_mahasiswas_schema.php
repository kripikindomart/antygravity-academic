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
            // Drop column if exists to ensure clean slate for FK
            if (Schema::hasColumn('nilai_mahasiswas', 'kelas_matakuliah_id')) {
                // We must drop it using separate schema call usually, or just rely on dropColumn here
                $table->dropColumn('kelas_matakuliah_id');
            }
        });

        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            // Re-add column with correct FK
            $table->foreignId('kelas_matakuliah_id')->after('id')->constrained('kelas_matakuliah')->cascadeOnDelete();

            // Re-add missing FK for komponen_nilai_id
            // Check if constraint exists? Hard to check portable. 
            // We assume it's missing because of previous error.
            try {
                $table->foreign('komponen_nilai_id')->references('id')->on('komponen_nilais')->cascadeOnDelete();
            } catch (\Exception $e) {
                // Ignore if exists
            }

            // Add unique index
            $table->unique(['kelas_matakuliah_id', 'komponen_nilai_id', 'mahasiswa_id'], 'unique_kelas_komponen_mhs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            $table->dropForeign(['kelas_matakuliah_id']);
            // We won't drop column here to avoid data loss in rollback if possible, or yes?
            // Standard rollback should allow drop
            $table->dropColumn('kelas_matakuliah_id');
            $table->dropForeign(['komponen_nilai_id']);
            $table->dropUnique('unique_kelas_komponen_mhs');
        });
    }
};
