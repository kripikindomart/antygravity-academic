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
        // CPL - Capaian Pembelajaran Lulusan
        Schema::create('cpls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained('kurikulums')->cascadeOnDelete();
            $table->string('kode', 20)->comment('CPL-1, CPL-2, etc');
            $table->text('deskripsi');
            $table->enum('kategori', ['sikap', 'pengetahuan', 'keterampilan_umum', 'keterampilan_khusus']);
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kurikulum_id', 'kode']);
        });

        // CPMK - Capaian Pembelajaran Mata Kuliah
        Schema::create('cpmks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->string('kode', 20)->comment('CPMK-1, CPMK-2, etc');
            $table->text('deskripsi');
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mata_kuliah_id', 'kode']);
        });

        // Sub-CPMK
        Schema::create('sub_cpmks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
            $table->string('kode', 30)->comment('Sub-CPMK-1.1');
            $table->text('deskripsi');
            $table->tinyInteger('pertemuan_ke')->nullable()->comment('Pertemuan ke-1 sampai 16');
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cpmk_id', 'kode']);
        });

        // Mapping CPL - CPMK
        Schema::create('cpl_cpmk_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
            $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
            $table->decimal('bobot', 5, 2)->default(0)->comment('Bobot kontribusi dalam persen');
            $table->timestamps();

            $table->unique(['cpl_id', 'cpmk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpl_cpmk_mappings');
        Schema::dropIfExists('sub_cpmks');
        Schema::dropIfExists('cpmks');
        Schema::dropIfExists('cpls');
    }
};
