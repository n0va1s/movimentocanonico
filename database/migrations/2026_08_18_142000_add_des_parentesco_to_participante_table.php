<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->string('des_parentesco', 255)->nullable()->after('tip_cor_troca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participante', function (Blueprint $table) {
            $table->dropColumn('des_parentesco');
        });
    }
};
