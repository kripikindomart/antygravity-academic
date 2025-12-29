<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->foreignId('semester_masuk_id')->nullable()->constrained('semesters')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropForeign(['semester_masuk_id']);
            $table->dropColumn('semester_masuk_id');
        });
    }
};