<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_pertemuans', 'ruangan_id')) {
                $table->foreignId('ruangan_id')->nullable()->after('dosen_id')->constrained('ruangans')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            $table->dropForeign(['ruangan_id']);
            $table->dropColumn('ruangan_id');
        });
    }
};
