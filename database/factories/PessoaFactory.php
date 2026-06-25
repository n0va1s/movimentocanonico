<?php

namespace Database\Factories;

use App\Enums\EstadoCivil;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PessoaFactory extends Factory
{
    protected $model = Pessoa::class;

    public function definition(): array
    {
        return [
            'idt_usuario' => User::factory(),
            'idt_parceiro' => null,
            'num_cpf_pessoa' => $this->faker->numerify('###########'),
            'nom_pessoa' => $this->faker->name(),
            'nom_apelido' => $this->faker->lastName(),
            'tip_genero' => $this->faker->randomElement(Genero::cases())->value,
            'tel_pessoa' => $this->faker->numerify('(##) #####-####'),
            'eml_pessoa' => $this->faker->unique()->safeEmail(),
            'des_endereco' => $this->faker->address(),
            'dat_nascimento' => Carbon::parse($this->faker->date('Y-m-d', '-20 years'))->format('Y-m-d'),
            'tam_camiseta' => $this->faker->randomElement(TamanhoCamiseta::cases())->value,
            'tip_habilidade' => $this->faker->randomElement(HabilidadePrincipal::cases())->value,
            'nom_profissao' => $this->faker->jobTitle(),
            'tip_estado_civil' => $this->faker->randomElement(EstadoCivil::cases())->value,
            'ind_restricao' => $this->faker->boolean(),
            'qtd_pontos_total' => 0,
        ];
    }
}
