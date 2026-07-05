<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Adicionar a coluna ind_voluntariar com default true
        if (!Schema::hasColumn('tipo_equipe', 'ind_voluntariar')) {
            Schema::table('tipo_equipe', function (Blueprint $table) {
                $table->boolean('ind_voluntariar')->default(true)->after('des_grupo');
            });
        }

        // 2. Garantir que todas as equipes existentes tenham ind_voluntariar = true por padrão
        DB::table('tipo_equipe')->update(['ind_voluntariar' => true]);

        // 3. Ajustar/Semear as equipes do Segue-Me caso o movimento exista
        $movimento = DB::table('tipo_movimento')->where('des_sigla', 'Segue-Me')->first();

        if ($movimento) {
            $equipes = [
                'Animação' => true,
                'Canto' => true,
                'Círculos' => true,
                'Cozinha' => true,
                'Estacionamento' => true,
                'Faxina' => true,
                'Gráfica' => true,
                'Lanche' => true,
                'Liturgia e Vigilia' => true,
                'Mini-mercado' => true,
                'Sala' => true,
                'Visitação' => true,
                'Prover' => true,
                'Vigilia Paroquial' => true,
                'Comando' => false,
                'Equipe Dirigente' => false,
                'Espiritualizadores' => false,
            ];

            // Garante que todas as equipes corretas existam e estejam ativas
            foreach ($equipes as $nome => $voluntariar) {
                DB::table('tipo_equipe')->updateOrInsert(
                    [
                        'idt_movimento' => $movimento->idt_movimento,
                        'des_grupo' => $nome,
                    ],
                    [
                        'ind_voluntariar' => $voluntariar,
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]
                );
            }

            // Remove/Soft-deleta as equipes antigas do Segue-Me que não estão na lista
            DB::table('tipo_equipe')
                ->where('idt_movimento', $movimento->idt_movimento)
                ->whereNotIn('des_grupo', array_keys($equipes))
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tipo_equipe', 'ind_voluntariar')) {
            Schema::table('tipo_equipe', function (Blueprint $table) {
                $table->dropColumn('ind_voluntariar');
            });
        }
    }
};
