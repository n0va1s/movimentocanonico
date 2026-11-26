<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evento', fn (Blueprint $table) => $table->boolean('ind_candidatura_aberta')->default(false));
    }

    public function down(): void
    {
        Schema::table('evento', fn (Blueprint $table) => $table->dropColumn('ind_candidatura_aberta'));
    }
};
