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
        Schema::table('program_studis', function (Blueprint $table) {
            // Gugus Kendali Mutu (GKM) - FK to dosens
            $table->foreignId('gkm_id')->nullable()->after('sekretaris_id')
                ->comment('Gugus Kendali Mutu / Koordinator RMK');

            // Staf Prodi - JSON array of staff names (manual input for now)
            $table->json('staf_prodi')->nullable()->after('gkm_id')
                ->comment('Array of staff names: ["Nama Staf 1", "Nama Staf 2"]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_studis', function (Blueprint $table) {
            $table->dropColumn(['gkm_id', 'staf_prodi']);
        });
    }
};
