<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidatura_voluntario', function (Blueprint $table) {
            $table->string('tipo_candidatura', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidatura_voluntario', function (Blueprint $table) {
            $table->dropColumn('tipo_candidatura');
        });
    }
};
