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
        Schema::table('rps', function (Blueprint $table) {
            // Approval status and verification
            $table->string('approval_status')->default('draft')->after('status');
            $table->string('verification_code')->nullable()->unique()->after('approval_status');

            // GKM/Koordinator RMK Approval
            $table->foreignId('approved_by_gkm_id')->nullable()->after('verification_code')
                ->constrained('dosens')->nullOnDelete();
            $table->timestamp('approved_by_gkm_at')->nullable()->after('approved_by_gkm_id');
            $table->text('gkm_notes')->nullable()->after('approved_by_gkm_at');

            // Kaprodi Approval
            $table->foreignId('approved_by_kaprodi_id')->nullable()->after('gkm_notes')
                ->constrained('dosens')->nullOnDelete();
            $table->timestamp('approved_by_kaprodi_at')->nullable()->after('approved_by_kaprodi_id');
            $table->text('kaprodi_notes')->nullable()->after('approved_by_kaprodi_at');

            // Index for faster queries
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rps', function (Blueprint $table) {
            $table->dropForeign(['approved_by_gkm_id']);
            $table->dropForeign(['approved_by_kaprodi_id']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn([
                'approval_status',
                'verification_code',
                'approved_by_gkm_id',
                'approved_by_gkm_at',
                'gkm_notes',
                'approved_by_kaprodi_id',
                'approved_by_kaprodi_at',
                'kaprodi_notes',
            ]);
        });
    }
};
