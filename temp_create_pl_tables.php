<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "--- Creating Profil Lulusan Tables ---\n";

// 1. profil_lulusans
try {
    if (!Schema::hasTable('profil_lulusans')) {
        Schema::create('profil_lulusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained()->cascadeOnDelete();
            $table->string('kode');
            $table->text('deskripsi');
            $table->timestamps();
        });
        echo "[SUCCESS] Table 'profil_lulusans' created.\n";
    } else {
        echo "[INFO] Table 'profil_lulusans' already exists.\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] Creating profil_lulusans: " . $e->getMessage() . "\n";
}

// 2. cpl_profil_lulusan (Pivot)
try {
    if (!Schema::hasTable('cpl_profil_lulusan')) {
        Schema::create('cpl_profil_lulusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete(); // FK to CPL
            $table->foreignId('profil_lulusan_id')->constrained('profil_lulusans')->cascadeOnDelete();
            $table->decimal('skor', 5, 2)->nullable(); // e.g. 16.66
            $table->timestamps();

            $table->unique(['cpl_id', 'profil_lulusan_id'], 'cpl_pl_unique');
        });
        echo "[SUCCESS] Table 'cpl_profil_lulusan' created.\n";
    } else {
        echo "[INFO] Table 'cpl_profil_lulusan' already exists.\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] Creating cpl_profil_lulusan: " . $e->getMessage() . "\n";
}
