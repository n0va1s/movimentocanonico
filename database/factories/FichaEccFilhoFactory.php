<?php

namespace Database\Factories;

use App\Models\Ficha;
use App\Models\FichaEccFilho;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FichaEccFilho>
 */
class FichaEccFilhoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'idt_ficha' => Ficha::factory(),
            'num_cpf_filho' => $this->faker->numerify('###########'),
            'nom_filho' => $this->faker->firstName().' '.$this->faker->lastName(),
            'dat_nascimento_filho' => $this->faker->dateTimeBetween('-18 years', 'now')->format('Y-m-d'),
            'eml_filho' => $this->faker->optional(0.7)->safeEmail(),
            'tel_filho' => $this->faker->optional(0.6)->phoneNumber(),
        ];
    }

    /**
     * Indica que o filho é do sexo masculino.
     */
    public function masculino(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'nom_filho' => $this->faker->firstName('male').' '.$this->faker->lastName(),
            ];
        });
    }

    /**
     * Indica que o filho é do sexo feminino.
     */
    public function feminino(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'nom_filho' => $this->faker->firstName('female').' '.$this->faker->lastName(),
            ];
        });
    }

    /**
     * Define uma data de nascimento específica.
     */
    public function comDataNascimento($data): static
    {
        return $this->state(function (array $attributes) use ($data) {
            return [
                'dat_nascimento_filho' => $data,
            ];
        });
    }

    /**
     * Define um email específico.
     */
    public function comEmail($email): static
    {
        return $this->state(function (array $attributes) use ($email) {
            return [
                'eml_filho' => $email,
            ];
        });
    }

    /**
     * Define um telefone específico.
     */
    public function comTelefone($telefone): static
    {
        return $this->state(function (array $attributes) use ($telefone) {
            return [
                'tel_filho' => $telefone,
            ];
        });
    }

    /**
     * Define um nome específico.
     */
    public function comNome($nome): static
    {
        return $this->state(function (array $attributes) use ($nome) {
            return [
                'nom_filho' => $nome,
            ];
        });
    }

    /**
     * Sem email.
     */
    public function semEmail(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'eml_filho' => null,
            ];
        });
    }

    /**
     * Sem telefone.
     */
    public function semTelefone(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'tel_filho' => null,
            ];
        });
    }
}
