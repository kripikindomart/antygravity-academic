<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "--- Fixing Database Schema ---\n";

// 1. Fix kurikulum_mata_kuliah: Add 'semester' column
try {
    if (Schema::hasTable('kurikulum_mata_kuliah')) {
        if (!Schema::hasColumn('kurikulum_mata_kuliah', 'semester')) {
            Schema::table('kurikulum_mata_kuliah', function (Blueprint $table) {
                // Add semester column, nullable or default 1?
                // Make it tinyInteger
                $table->tinyInteger('semester')->default(1)->after('mata_kuliah_id');
            });
            echo "[SUCCESS] Added 'semester' column to 'kurikulum_mata_kuliah'.\n";
        } else {
            echo "[INFO] Column 'semester' already exists in 'kurikulum_mata_kuliah'.\n";
        }
    } else {
        echo "[ERROR] Table 'kurikulum_mata_kuliah' not found.\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] Fixing pivot: " . $e->getMessage() . "\n";
}

// 2. Create cpl_mata_kuliah table
try {
    if (!Schema::hasTable('cpl_mata_kuliah')) {
        Schema::create('cpl_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['kurikulum_id', 'mata_kuliah_id', 'cpl_id'], 'cpl_mk_unique');
        });
        echo "[SUCCESS] Table 'cpl_mata_kuliah' created.\n";
    } else {
        echo "[INFO] Table 'cpl_mata_kuliah' already exists.\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] Creating cpl_mk: " . $e->getMessage() . "\n";
}
