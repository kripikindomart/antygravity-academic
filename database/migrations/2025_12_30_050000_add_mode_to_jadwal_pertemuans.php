<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_pertemuans', 'mode')) {
                $table->enum('mode', ['online', 'offline', 'hybrid'])->default('offline')->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
