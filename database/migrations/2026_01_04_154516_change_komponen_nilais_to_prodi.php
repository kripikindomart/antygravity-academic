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
        Schema::table('komponen_nilais', function (Blueprint $table) {
            // Drop old FK
            $table->dropForeign(['kelas_matakuliah_id']);
            $table->dropColumn('kelas_matakuliah_id');

            // Add new FK to Prodi
            $table->foreignId('prodi_id')->after('id')->constrained('program_studis')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komponen_nilais', function (Blueprint $table) {
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');

            $table->foreignId('kelas_matakuliah_id')->after('id')->constrained('kelas_matakuliah')->cascadeOnDelete();
        });
    }
};
