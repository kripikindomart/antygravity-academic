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
        Schema::create('rekap_nilais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_matakuliah_id')->constrained('kelas_matakuliah')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();

            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_huruf', 5)->nullable();
            $table->decimal('nilai_indeks', 4, 2)->nullable();

            $table->enum('status', ['draft', 'submitted', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['kelas_matakuliah_id', 'mahasiswa_id'], 'unique_rekap_mhs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_nilais');
    }
};
