<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
        echo "Table cpl_mata_kuliah created successfully.\n";
    } else {
        echo "Table already exists.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
