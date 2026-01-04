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
        Schema::table('kelas_matakuliah', function (Blueprint $table) {
            $table->string('custom_initials')->nullable()->after('matrix_group');
            $table->string('custom_color')->nullable()->after('custom_initials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas_matakuliah', function (Blueprint $table) {
            $table->dropColumn(['custom_initials', 'custom_color']);
        });
    }
};
