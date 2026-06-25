<?php

namespace Database\Factories;

use App\Models\Ficha;
use App\Models\FichaFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

class FichaFotoFactory extends Factory
{
    protected $model = FichaFoto::class;

    public function definition(): array
    {
        return [
            'idt_ficha' => Ficha::factory(),
            'med_foto' => 'fichas/'.$this->faker->uuid().'.jpg',
            'med_conjuge' => null,
        ];
    }

    /**
     * Inclui foto do cônjuge (usado para fichas ECC).
     */
    public function comConjuge(): static
    {
        return $this->state(fn () => [
            'med_conjuge' => 'fichas/'.$this->faker->uuid().'.jpg',
        ]);
    }
}
