<?php

namespace Database\Factories;

use App\Enums\ComoSoube;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Enums\TipoSituacao;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FichaFactory extends Factory
{
    protected $model = Ficha::class;

    public function definition(): array
    {
        return [
            'idt_evento' => Evento::inRandomOrder()->first()?->idt_evento,
            'idt_pessoa' => Pessoa::inRandomOrder()->first()?->idt_pessoa ?? Pessoa::factory(),
            'num_cpf_candidato' => $this->faker->numerify('###########'),
            'tip_genero' => $this->faker->randomElement(Genero::cases()),
            'nom_candidato' => $this->faker->name(),
            'nom_apelido' => $this->faker->firstName(),
            'dat_nascimento' => $this->faker->date('Y-m-d', '-15 years'),
            'tel_candidato' => $this->faker->numerify('619########'),
            'eml_candidato' => $this->faker->safeEmail(),
            'des_endereco' => $this->faker->address(),
            'tam_camiseta' => $this->faker->randomElement(TamanhoCamiseta::cases())->value,
            'tip_como_soube' => $this->faker->randomElement(ComoSoube::cases())->value,
            'tip_habilidade' => $this->faker->randomElement(HabilidadePrincipal::cases())->value,
            'ind_catolico' => $this->faker->boolean(),
            'ind_toca_instrumento' => $this->faker->boolean(),
            'ind_consentimento' => true,
            'tip_situacao' => TipoSituacao::CADASTRADO,
            'ind_restricao' => $this->faker->boolean(),
            'txt_observacao' => $this->faker->sentence(),
            'nom_profissao' => $this->faker->jobTitle(),

            // Campos de Auditoria
            'usu_inclusao' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'usu_alteracao' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
