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
        Schema::table('jurnals', function (Blueprint $table) {
            // Change bukti_perkuliahan from string to JSON to support multiple files
            $table->json('file_materi')->nullable()->after('catatan')->comment('Array of file paths for materi (PPT/PDF)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnals', function (Blueprint $table) {
            $table->dropColumn('file_materi');
        });
    }
};
