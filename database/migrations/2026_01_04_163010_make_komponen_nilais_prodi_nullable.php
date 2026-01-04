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
            // Drop FK first, then modify column
            $table->dropForeign(['prodi_id']);
            $table->foreignId('prodi_id')->nullable()->change();

            // Re-add FK with nullable
            $table->foreign('prodi_id')->references('id')->on('program_studis')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komponen_nilais', function (Blueprint $table) {
            // Remove nullable
            $table->dropForeign(['prodi_id']);
            $table->foreignId('prodi_id')->nullable(false)->change();
            $table->foreign('prodi_id')->references('id')->on('program_studis')->cascadeOnDelete();
        });
    }
};
