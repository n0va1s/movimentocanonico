<?php

namespace Database\Factories;

use App\Enums\CorTroca;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipanteFactory extends Factory
{
    protected $model = Participante::class;

    public function definition(): array
    {
        return [
            'idt_pessoa' => Pessoa::factory(),
            'idt_evento' => Evento::factory(),
            // 50% chance de ter cor de troca
            'tip_cor_troca' => $this->faker->randomElement(CorTroca::cases()),
            'ind_taxa_pagou' => $this->faker->boolean(),
            'ind_presente' => $this->faker->boolean(),
        ];
    }
}
