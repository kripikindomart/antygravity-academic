<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('kelas_matakuliah', function (Blueprint $table) {
            if (!Schema::hasColumn('kelas_matakuliah', 'total_sesi')) {
                $table->integer('total_sesi')->default(16)->after('jam_selesai')->nullable();
            }
            if (!Schema::hasColumn('kelas_matakuliah', 'sesi_per_pertemuan')) {
                $table->integer('sesi_per_pertemuan')->default(1)->after('total_sesi')->nullable();
            }
            if (!Schema::hasColumn('kelas_matakuliah', 'pertemuan_uts')) {
                $table->integer('pertemuan_uts')->default(8)->after('sesi_per_pertemuan')->nullable();
            }
            if (!Schema::hasColumn('kelas_matakuliah', 'pertemuan_uas')) {
                $table->integer('pertemuan_uas')->default(16)->after('pertemuan_uts')->nullable();
            }
        });

        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_pertemuans', 'sesi_mulai')) {
                $table->integer('sesi_mulai')->nullable()->after('tanggal');
            }
            if (!Schema::hasColumn('jadwal_pertemuans', 'sesi_selesai')) {
                $table->integer('sesi_selesai')->nullable()->after('sesi_mulai');
            }
            if (!Schema::hasColumn('jadwal_pertemuans', 'tipe')) {
                $table->string('tipe', 50)->default('Kuliah')->after('sesi_selesai'); // Kuliah, UTS, UAS
            }
            if (!Schema::hasColumn('jadwal_pertemuans', 'dosen_id')) {
                $table->foreignId('dosen_id')->nullable()->after('tipe')->constrained('dosens')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('kelas_matakuliah', function (Blueprint $table) {
            $table->dropColumn(['total_sesi', 'sesi_per_pertemuan', 'pertemuan_uts', 'pertemuan_uas']);
        });

        Schema::table('jadwal_pertemuans', function (Blueprint $table) {
            // $table->dropForeign(['dosen_id']); // Can vary based on constraint name
            $table->dropColumn(['sesi_mulai', 'sesi_selesai', 'tipe', 'dosen_id']);
        });
    }
};
