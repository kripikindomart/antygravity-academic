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
        Schema::table('jadwals', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwals', 'kelas_id')) {
                // Add kelas_id after id, nullable initially to avoid errors with existing data
                $table->foreignId('kelas_id')->nullable()->after('id')->constrained('kelas')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            if (Schema::hasColumn('jadwals', 'kelas_id')) {
                $table->dropForeign(['kelas_id']);
                $table->dropColumn('kelas_id');
            }
        });
    }
};
