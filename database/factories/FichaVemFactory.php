<?php

namespace Database\Factories;

use App\Models\FichaVem;
use App\Models\TipoResponsavel;
use Illuminate\Database\Eloquent\Factories\Factory;

class FichaVemFactory extends Factory
{
    protected $model = FichaVem::class;

    public function definition(): array
    {
        return [
            'idt_ficha' => null, // Será preenchido pelo Seeder para evitar recursividade
            'idt_falar_com' => TipoResponsavel::inRandomOrder()->first()?->idt_responsavel ?? TipoResponsavel::factory(),
            'des_onde_estuda' => $this->faker->company(),
            'des_mora_quem' => $this->faker->words(3, true),
            'nom_pai' => $this->faker->name('male'),
            'tel_pai' => $this->faker->numerify('619########'),
            'eml_pai' => $this->faker->unique()->lexify('????????@test.com'),
            'nom_mae' => $this->faker->name('female'),
            'tel_mae' => $this->faker->numerify('619########'),
            'eml_mae' => $this->faker->unique()->lexify('????????@test.com'),
            'nom_responsavel' => $this->faker->name(),
            'tel_responsavel' => $this->faker->numerify('619########'),
            'eml_responsavel' => $this->faker->unique()->lexify('????????@test.com'),
            'ind_batizado' => $this->faker->boolean(),
            'ind_primeira_comunhao' => $this->faker->boolean(),
            'ind_crismado' => $this->faker->boolean(),
            'nom_paroquia' => $this->faker->company().' Parish',
        ];
    }
}
