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
            // PLT status for Kaprodi and Sekretaris
            $table->boolean('is_kaprodi_plt')->default(false)->after('kaprodi_id')
                ->comment('True if Kaprodi is acting/PLT (Pelaksana Tugas)');
            $table->boolean('is_sekretaris_plt')->default(false)->after('sekretaris_id')
                ->comment('True if Sekretaris is acting/PLT (Pelaksana Tugas)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_studis', function (Blueprint $table) {
            $table->dropColumn(['is_kaprodi_plt', 'is_sekretaris_plt']);
        });
    }
};
