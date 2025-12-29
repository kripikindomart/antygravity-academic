<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kelas_mk_ruangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_matakuliah_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
            $table->foreignId('ruangan_id')->constrained('ruangans')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_mk_ruangan');
    }
};
