<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            // Override columns for custom time per meeting
            if (!Schema::hasColumn('jadwal_pertemuans', 'jam_mulai')) {
                $table->time('jam_mulai')->nullable()->after('tanggal');
            }
            if (!Schema::hasColumn('jadwal_pertemuans', 'jam_selesai')) {
                $table->time('jam_selesai')->nullable()->after('jam_mulai');
            }
        });
    }

    public function down()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai', 'jam_selesai']);
        });
    }
};
