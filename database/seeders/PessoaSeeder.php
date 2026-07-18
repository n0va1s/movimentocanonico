<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\PessoaSaude;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PessoaSeeder extends Seeder
{
    public function run(): void
    {
        // Desativa logs de query para economizar memória em seeders grandes
        DB::disableQueryLog();

        // 1. Gerar Pessoas em Massa (Sem Parceiro)
        $this->command->info('Gerando 500 pessoas simples...');
        $pessoasSimples = Pessoa::factory(500)->make(['idt_parceiro' => null])
            ->map(function ($p) {
                $p->setAppends([]);
                return $p->toArray();
            })
            ->toArray();

        foreach (array_chunk($pessoasSimples, 100) as $chunk) {
            Pessoa::insert($chunk);
        }

        // 2. Gerar Pessoas para Pareamento (Usando insert em vez de create)
        $this->command->info('Gerando 150 pessoas para pareamento...');
        $pessoasParaPar = Pessoa::factory(150)->make(['idt_parceiro' => null])
            ->map(function ($p) {
                $p->setAppends([]);
                return $p->toArray();
            })
            ->toArray();
        foreach (array_chunk($pessoasParaPar, 100) as $chunk) {
            Pessoa::insert($chunk);
        }

        // 3. Pareamento Ultrarrápido com Transação
        $pessoasSemParceiro = Pessoa::whereNull('idt_parceiro')->pluck('idt_pessoa')->toArray();
        shuffle($pessoasSemParceiro);

        $this->command->info('Realizando pareamento...');

        DB::transaction(function () use (&$pessoasSemParceiro) {
            while (count($pessoasSemParceiro) >= 2) {
                $id1 = array_pop($pessoasSemParceiro);
                $id2 = array_pop($pessoasSemParceiro);

                // O segredo aqui é que o DB::transaction no SQLite mantém o arquivo aberto
                // e faz o commit de todos os updates de uma só vez no final.
                DB::table('pessoa')->where('idt_pessoa', $id1)->update(['idt_parceiro' => $id2]);
                DB::table('pessoa')->where('idt_pessoa', $id2)->update(['idt_parceiro' => $id1]);
            }
        });

        // 4. Saúde e Fotos em Massa
        $this->command->info('Gerando dados de saúde...');
        $saudeData = PessoaSaude::factory(100)->make()->toArray();
        foreach (array_chunk($saudeData, 100) as $chunk) {
            PessoaSaude::insert($chunk);
        }
    }
}
