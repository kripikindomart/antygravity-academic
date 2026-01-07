<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->enum('data_source', ['dosen', 'mahasiswa', 'prodi', 'kelas', 'mata_kuliah'])->nullable()->after('tipe');
            $table->json('data_filter')->nullable()->after('data_source'); // {"jadwal_only": true, "tahun_akademik_id": 1}
        });
    }

    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn(['data_source', 'data_filter']);
        });
    }
};
