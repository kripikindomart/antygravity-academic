<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify existing status enum to include new workflow states
        DB::statement("ALTER TABLE `rekap_nilais` MODIFY COLUMN `status` ENUM('draft', 'submitted', 'pending_approval', 'approved', 'published') NOT NULL DEFAULT 'draft'");

        // Add approved_at if not exists
        if (!Schema::hasColumn('rekap_nilais', 'approved_at')) {
            Schema::table('rekap_nilais', function (Blueprint $table) {
                $table->datetime('approved_at')->nullable()->after('published_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum to original values
        DB::statement("ALTER TABLE `rekap_nilais` MODIFY COLUMN `status` ENUM('draft', 'submitted', 'published') NOT NULL DEFAULT 'draft'");

        if (Schema::hasColumn('rekap_nilais', 'approved_at')) {
            Schema::table('rekap_nilais', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
