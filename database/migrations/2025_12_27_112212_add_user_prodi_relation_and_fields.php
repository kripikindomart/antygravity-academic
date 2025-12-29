<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * - Staff Prodi can be linked to specific program studi
     * - Users can be linked to multiple prodi (for staff managing multiple programs)
     */
    public function up(): void
    {
        // Pivot table for user-prodi relation (for staff_prodi)
        Schema::create('user_prodi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_studi_id')->constrained('program_studis')->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Primary prodi for user
            $table->timestamps();

            $table->unique(['user_id', 'program_studi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_prodi');
    }
};
