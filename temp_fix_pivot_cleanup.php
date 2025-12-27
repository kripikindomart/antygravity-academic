<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (Schema::hasColumn('kurikulum_mata_kuliah', 'semester_rekomendasi')) {
        Schema::table('kurikulum_mata_kuliah', function (Blueprint $table) {
            $table->dropColumn('semester_rekomendasi');
        });
        echo "[SUCCESS] Dropped 'semester_rekomendasi' from 'kurikulum_mata_kuliah'.\n";
    } else {
        echo "[INFO] Column 'semester_rekomendasi' does not exist.\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
