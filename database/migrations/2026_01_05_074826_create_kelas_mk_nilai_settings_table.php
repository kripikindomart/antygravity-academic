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
        Schema::create('kelas_mk_nilai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_matakuliah_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->constrained('dosens')->cascadeOnDelete(); // null = global setting for class
            $table->datetime('deadline')->nullable();
            $table->boolean('allow_view_others')->default(false); // Can this dosen see other dosen's grades?
            $table->timestamps();

            $table->unique(['kelas_matakuliah_id', 'dosen_id'], 'unique_kelas_dosen_setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_mk_nilai_settings');
    }
};
