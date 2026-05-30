<?php

namespace Database\Factories;

use App\Models\Contato;
use App\Models\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContatoFactory extends Factory
{
    protected $model = Contato::class;

    public function definition(): array
    {
        $dataInicio = $this->faker->dateTimeBetween('-1 year', '+1 month');
        $dataTermino = (clone $dataInicio)->modify('+3 days');

        return [
            'idt_movimento' => fn () => TipoMovimento::inRandomOrder()->first()?->idt_movimento ?? TipoMovimento::factory()->create()->idt_movimento,
            'dat_contato' => now(),
            'nom_contato' => $this->faker->name(),
            'eml_contato' => $this->faker->email(),
            'tel_contato' => $this->faker->phoneNumber(),
            'txt_mensagem' => $this->faker->text(),
        ];
    }
}
