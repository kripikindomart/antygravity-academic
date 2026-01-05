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
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            // Add dosen_id to track which lecturer gave this specific grade
            $table->foreignId('dosen_id')->nullable()->after('grader_id')->constrained('dosens')->nullOnDelete();

            // Add status for submission workflow (draft = can edit, submitted = locked for dosen)
            $table->enum('status', ['draft', 'submitted'])->default('draft')->after('feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_mahasiswas', function (Blueprint $table) {
            $table->dropForeign(['dosen_id']);
            $table->dropColumn(['dosen_id', 'status']);
        });
    }
};
