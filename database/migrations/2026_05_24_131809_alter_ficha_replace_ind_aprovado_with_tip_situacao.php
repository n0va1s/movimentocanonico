<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ficha', function (Blueprint $table) {
            $table->string('tip_situacao', 1)->default('N')->after('ind_consentimento');
        });

        // Migrar os dados existentes de forma segura
        DB::table('ficha')->where('ind_aprovado', true)->update(['tip_situacao' => 'A']);
        DB::table('ficha')->where('ind_aprovado', false)->update(['tip_situacao' => 'N']);

        Schema::table('ficha', function (Blueprint $table) {
            $table->dropColumn('ind_aprovado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha', function (Blueprint $table) {
            $table->boolean('ind_aprovado')->default(false)->after('ind_consentimento');
        });

        // Migrar de volta de forma reversível
        DB::table('ficha')->where('tip_situacao', 'A')->update(['ind_aprovado' => true]);
        DB::table('ficha')->where('tip_situacao', '!=', 'A')->update(['ind_aprovado' => false]);

        Schema::table('ficha', function (Blueprint $table) {
            $table->dropColumn('tip_situacao');
        });
    }
};
