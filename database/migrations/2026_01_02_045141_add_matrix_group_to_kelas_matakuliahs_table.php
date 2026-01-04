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
            $table->tinyInteger('matrix_group')->default(1)->after('tanggal_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kelas_matakuliah', function (Blueprint $table) {
            $table->dropColumn('matrix_group');
        });
    }
};
