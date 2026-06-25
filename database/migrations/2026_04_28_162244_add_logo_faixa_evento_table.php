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
        Schema::table('evento', function (Blueprint $table) {
            $table->char('tip_faixa_etaria', 5)->nullable(); // Ex:12-15, 60+
        });

        Schema::table('evento_foto', function (Blueprint $table) {
            $table->string('med_foto')->nullable()->change();
            $table->string('med_logo')->nullable()->after('med_foto');
        });

        Schema::dropIfExists('presenca');
        Schema::dropIfExists('tipo_situacao');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropColumn('tip_faixa_etaria');
        });

        Schema::table('evento_foto', function (Blueprint $table) {
            $table->string('med_foto')->nullable(false)->change();
            $table->dropColumn('med_logo');
        });
    }
};
