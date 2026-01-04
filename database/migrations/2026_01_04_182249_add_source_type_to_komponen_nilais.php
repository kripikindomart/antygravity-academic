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
        Schema::table('komponen_nilais', function (Blueprint $table) {
            // source_type: manual (default), kehadiran (from attendance data)
            $table->string('source_type', 20)->default('manual')->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komponen_nilais', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
