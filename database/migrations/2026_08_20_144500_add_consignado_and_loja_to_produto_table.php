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
        Schema::table('produto', function (Blueprint $table) {
            if (!Schema::hasColumn('produto', 'idt_evento')) {
                $table->foreignId('idt_evento')
                    ->nullable()
                    ->after('idt_produto')
                    ->constrained('evento', 'idt_evento')
                    ->onDelete('cascade');
            }
            if (!Schema::hasColumn('produto', 'ind_consignado')) {
                $table->boolean('ind_consignado')->default(false)->after('ind_favorito');
            }
            if (!Schema::hasColumn('produto', 'nom_loja')) {
                $table->string('nom_loja', 100)->nullable()->after('ind_consignado');
            }
            if (!Schema::hasColumn('produto', 'cod_produto_loja')) {
                $table->string('cod_produto_loja', 50)->nullable()->after('nom_loja');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            if (Schema::hasColumn('produto', 'cod_produto_loja')) {
                $table->dropColumn('cod_produto_loja');
            }
            if (Schema::hasColumn('produto', 'nom_loja')) {
                $table->dropColumn('nom_loja');
            }
            if (Schema::hasColumn('produto', 'ind_consignado')) {
                $table->dropColumn('ind_consignado');
            }
            if (Schema::hasColumn('produto', 'idt_evento')) {
                $table->dropForeign(['idt_evento']);
                $table->dropColumn('idt_evento');
            }
        });
    }
};
