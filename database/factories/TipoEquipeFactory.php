<?php

namespace Database\Factories;

use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoEquipeFactory extends Factory
{
    protected $model = TipoEquipe::class;

    public function definition(): array
    {
        // Valores aleatórios padrão, caso queira criar outros registros
        return [
            'idt_movimento' => TipoMovimento::factory(),
            'des_grupo' => $this->faker->word(),
        ];
    }

    /**
     * Retorna os dados fixos das equipes
     */
    public function defaults(): array
    {
        $movimentos = TipoMovimento::all()->keyBy('des_sigla');

        return [
            ['des_grupo' => 'Alimentação', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Bandinha', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Coordenação Geral', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Emaús', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Limpeza', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Oração', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Recepção', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Reportagem', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Sala', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Secretaria', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Vendinha', 'idt_movimento' => $movimentos['VEM']->idt_movimento],
            ['des_grupo' => 'Visitação', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Mini-Mercado', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Estacionamento', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Sala', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Ligação', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Alimentação', 'idt_movimento' => $movimentos['Segue-Me']->idt_movimento],
            ['des_grupo' => 'Sala', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Liturgia/Vigília', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Círculos', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Café e Minimercado', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Cozinha', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Ordem/Limpeza', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Visitação', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Externa', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Secretaria', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Compras', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
            ['des_grupo' => 'Palestras', 'idt_movimento' => $movimentos['ECC']->idt_movimento],
        ];
    }

    /**
     * Popula todas as equipes padrão
     */
    public static function seedDefaults(): void
    {
        foreach ((new self)->defaults() as $data) {
            TipoEquipe::firstOrCreate($data);
        }
    }
}
