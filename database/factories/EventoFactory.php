<?php

namespace Database\Factories;

use App\Enums\FaixaEtaria;
use App\Enums\TipoEvento;
use App\Models\Evento;
use App\Models\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition(): array
    {
        $dataLimite = $this->faker->dateTimeBetween('-2 year', '+1 month');
        $dataInicio = $this->faker->dateTimeBetween('-1 year', '+1 month');
        $dataTermino = (clone $dataInicio)->modify('+3 days');

        return [
            'idt_movimento' => TipoMovimento::inRandomOrder()->first()?->idt_movimento ?? TipoMovimento::factory(),
            'des_evento' => $this->faker->words(2, true),
            'num_evento' => $this->faker->numberBetween(1, 99),
            'dat_inicio' => $dataInicio->format('Y-m-d'),
            'dat_termino' => $dataTermino->format('Y-m-d'),
            'dat_limite_inscricao' => $dataLimite->format('Y-m-d'),
            'qtd_vaga' => $this->faker->numberBetween(40, 60),
            'val_camiseta' => $this->faker->randomNumber(2, 40),
            'val_trabalhador' => $this->faker->randomNumber(2, 45),
            'val_venista' => $this->faker->randomNumber(2, 50),
            'val_entrada' => $this->faker->randomNumber(2, 50),
            'tip_evento' => $this->faker->randomElement(TipoEvento::cases())->value,
            'tip_faixa_etaria' => $this->faker->randomElement(FaixaEtaria::cases())->value,
            'val_receita' => $this->faker->randomNumber(2, 50),
            'val_despesa' => $this->faker->randomNumber(2, 50),
            'txt_informacao' => $this->faker->optional()->paragraph(),
            'txt_relatorio' => $this->faker->optional()->paragraph(),
        ];
    }
}
