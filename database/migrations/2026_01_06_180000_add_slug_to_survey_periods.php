<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('survey_periods', function (Blueprint $table) {
            $table->string('slug', 10)->unique()->nullable()->after('nama');
            $table->boolean('allow_guest')->default(true)->after('is_mandatory');
        });
    }

    public function down(): void
    {
        Schema::table('survey_periods', function (Blueprint $table) {
            $table->dropColumn(['slug', 'allow_guest']);
        });
    }
};
