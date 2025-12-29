<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            if (Schema::hasColumn('mahasiswas', 'telepon') && !Schema::hasColumn('mahasiswas', 'no_hp')) {
                $table->renameColumn('telepon', 'no_hp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
             if (Schema::hasColumn('mahasiswas', 'no_hp') && !Schema::hasColumn('mahasiswas', 'telepon')) {
                $table->renameColumn('no_hp', 'telepon');
             }
        });
    }
};
