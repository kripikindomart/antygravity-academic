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
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            // Dosen attendance tracking for payroll
            $table->time('dosen_jam_masuk')->nullable()->after('dosen_id');
            $table->time('dosen_jam_keluar')->nullable()->after('dosen_jam_masuk');
            $table->boolean('dosen_hadir')->default(false)->after('dosen_jam_keluar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            $table->dropColumn(['dosen_jam_masuk', 'dosen_jam_keluar', 'dosen_hadir']);
        });
    }
};
