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
        Schema::create('rps_pengembang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rps_id')->constrained('rps')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('dosens')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['rps_id', 'dosen_id']);
        });

        // Migrate existing data from rps.dosen_id
        // dosen_id in rps table is actually user_id, so we need to find the matching dosen
        $rpsList = DB::table('rps')->whereNotNull('dosen_id')->get();
        foreach ($rpsList as $rps) {
            // Find dosen by user_id
            $dosen = DB::table('dosens')->where('user_id', $rps->dosen_id)->first();
            if ($dosen) {
                DB::table('rps_pengembang')->insertOrIgnore([
                    'rps_id' => $rps->id,
                    'dosen_id' => $dosen->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rps_pengembang');
    }
};
