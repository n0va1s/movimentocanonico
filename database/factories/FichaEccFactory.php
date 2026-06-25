<?php

namespace Database\Factories;

use App\Enums\EstadoCivil;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Models\FichaEcc;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FichaEccFactory extends Factory
{
    protected $model = FichaEcc::class;

    public function definition(): array
    {
        $dataCasamento = Carbon::parse(
            $this->faker->dateTimeBetween('-30 years', '-1 year')
        )->format('Y-m-d');

        return [
            // idt_ficha é preenchido pelo relacionamento (FichaFactory)
            'idt_ficha' => null,

            // ── Cônjuge ───────────────────────────────────────────────────────
            'nom_conjuge' => $this->faker->name(),
            'nom_apelido_conjuge' => $this->faker->firstName(),
            'num_cpf_conjuge' => $this->faker->numerify('###########'),
            'tip_genero_conjuge' => $this->faker->randomElement(Genero::cases())->value,
            'dat_nascimento_conjuge' => Carbon::parse(
                $this->faker->dateTimeBetween('-70 years', '-20 years')
            )->format('Y-m-d'),
            'tel_conjuge' => $this->faker->numerify('619########'),
            'eml_conjuge' => $this->faker->safeEmail(),
            'nom_profissao_conjuge' => $this->faker->jobTitle(),
            'ind_catolico_conjuge' => $this->faker->boolean(80),
            'tip_habilidade_conjuge' => $this->faker->randomElement(HabilidadePrincipal::cases())->value,
            'tam_camiseta_conjuge' => $this->faker->randomElement(TamanhoCamiseta::cases())->value,

            // ── Informações comuns do casal ───────────────────────────────────
            'tip_estado_civil' => $this->faker->randomElement([
                EstadoCivil::CASADO->value,
                EstadoCivil::SEGUNDA_UNIAO->value,
                EstadoCivil::UNIAO_ESTAVEL->value,
            ]),
            'nom_paroquia' => $this->faker->randomElement([
                'Paróquia Nossa Senhora do Lago',
                'Paróquia São José',
                'Paróquia Cristo Rei',
                'Paróquia Nossa Senhora Aparecida',
            ]),
            'dat_casamento' => $dataCasamento,
            'qtd_filhos' => $this->faker->numberBetween(0, 5),
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    /** Casal casado religiosamente */
    public function casadoReligioso(): static
    {
        return $this->state(fn () => [
            'tip_estado_civil' => EstadoCivil::CASADO->value,
            'dat_casamento' => Carbon::parse(
                $this->faker->dateTimeBetween('-20 years', '-1 year')
            )->format('Y-m-d'),
        ]);
    }

    /** Casal em segunda união */
    public function segundaUniao(): static
    {
        return $this->state(fn () => [
            'tip_estado_civil' => EstadoCivil::SEGUNDA_UNIAO->value,
        ]);
    }

    /** Casal em união estável */
    public function uniaoEstavel(): static
    {
        return $this->state(fn () => [
            'tip_estado_civil' => EstadoCivil::UNIAO_ESTAVEL->value,
            'dat_casamento' => null,
        ]);
    }

    /** Sem filhos */
    public function semFilhos(): static
    {
        return $this->state(fn () => [
            'qtd_filhos' => 0,
        ]);
    }

    /** Com número específico de filhos */
    public function comFilhos(int $quantidade = 2): static
    {
        return $this->state(fn () => [
            'qtd_filhos' => $quantidade,
        ]);
    }
}
