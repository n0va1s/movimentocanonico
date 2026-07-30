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
        // 1. Criar a tabela de histórico (Gamificacao)
        if (!Schema::hasTable('gamificacao')) {
            Schema::create('gamificacao', function (Blueprint $table) {
                $table->id('idt_gamificacao');
                $table->foreignId('idt_pessoa')->constrained('pessoa', 'idt_pessoa')->onDelete('cascade');
                $table->integer('qtd_pontos'); // Pode ser positivo ou negativo
                $table->string('des_motivo');   // Ex: "Trabalho no XXX VEM", "Participação Pós-VEM"

                // Polimorfismo: Para saber de qual model veio o ponto (Trabalhador ou Participante)
                $table->nullableMorphs('origem');
                $table->timestamps();
            });
        }

        // 2. Adicionar a coluna de saldo na tabela Pessoa
        Schema::table('pessoa', function (Blueprint $table) {
            if (!Schema::hasColumn('pessoa', 'qtd_pontos_total')) {
                $table->integer('qtd_pontos_total')->default(0)->after('ind_restricao');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamificacao');
    }
};
