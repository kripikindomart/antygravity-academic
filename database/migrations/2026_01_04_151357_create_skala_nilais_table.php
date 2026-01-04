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
        Schema::create('skala_nilais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('program_studis')->nullOnDelete();
            $table->string('huruf', 2); // A, A-, B+
            $table->decimal('bobot', 4, 2); // 4.00
            $table->decimal('min_nilai', 5, 2); // 85.00
            $table->decimal('max_nilai', 5, 2); // 100.00
            $table->boolean('status_lulus')->default(true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skala_nilais');
    }
};
