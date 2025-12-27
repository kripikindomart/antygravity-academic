<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * NIP, NIM, NIDN should be stored in dosens/mahasiswas tables,
     * not in the users table which is only for authentication.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'nim', 'nidn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique()->after('email');
            $table->string('nim')->nullable()->unique()->after('nip');
            $table->string('nidn')->nullable()->unique()->after('nim');
        });
    }
};
