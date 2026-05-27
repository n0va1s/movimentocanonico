<?php

namespace Database\Factories;

use App\Enums\Escolaridade;
use App\Enums\EscolaridadeSituacao;
use App\Enums\Religiao;
use App\Models\FichaSGM;
use App\Models\TipoResponsavel;
use Illuminate\Database\Eloquent\Factories\Factory;

class FichaSGMFactory extends Factory
{
    protected $model = FichaSGM::class;

    public function definition(): array
    {
        $cursosSuperiores = ['Direito', 'Medicina', 'Engenharia', 'Administração', 'Psicologia', 'Pedagogia'];
        $cursosMedio = ['Ensino Médio', 'Técnico em Informática', 'Técnico em Administração'];
        $escolaridade = fake()->randomElement(Escolaridade::cases());

        return [
            // Chave – preenchida pelo Seeder para evitar recursividade
            'idt_ficha' => null,

            // Responsável
            'idt_falar_com' => fn () => TipoResponsavel::first() ?? TipoResponsavel::factory()->create(),

            // ── Filiação ──────────────────────────────────────────────
            'nom_mae' => fake()->name('female'),
            'tel_mae' => fake()->numerify('(61) 9####-####'),
            'eml_mae' => fake()->optional(0.7)->safeEmail(),

            'nom_pai' => fake()->optional(0.8)->name('male'),
            'tel_pai' => fake()->optional(0.8)->numerify('(61) 9####-####'),
            'eml_pai' => fake()->optional(0.5)->safeEmail(),

            // ── Dados pessoais ────────────────────────────────────────
            'des_naturalidade' => fake()->city().' - '.fake()->stateAbbr(),

            // ── Escolaridade ──────────────────────────────────────────
            'tip_escolaridade' => $escolaridade,
            'tip_escolaridade_situacao' => fake()->randomElement(EscolaridadeSituacao::cases()),
            'des_curso' => $escolaridade === Escolaridade::SUPERIOR
                ? fake()->randomElement($cursosSuperiores)
                : fake()->optional(0.5)->randomElement($cursosMedio),
            'nom_instituicao' => fake()->optional(0.8)->company(),

            // ── Religião ──────────────────────────────────────────────
            'tip_religiao' => fake()->randomElement(Religiao::cases()),
            'nom_paroquia' => fake()->optional(0.6)->randomElement([
                'Nossa Senhora do Lago',
                'São José',
                'Santo Antônio',
                'Nossa Senhora Aparecida',
                'Santa Luzia',
            ]),
            'ind_batismo' => fake()->boolean(85),
            'ind_eucaristia' => fake()->boolean(75),
            'ind_crisma' => fake()->boolean(50),
            'des_participa_movimento' => fake()->optional(0.4)->randomElement([
                'Jovens da Paróquia',
                'Coral',
                'Catequese',
                'Pastoral da Juventude',
                'Grupo de Oração',
            ]),

            // ── Quem convidou ─────────────────────────────────────────
            'nom_convidou' => fake()->optional(0.8)->name(),
            'tel_convidou' => fake()->optional(0.7)->numerify('(61) 9####-####'),
            'end_convidou' => fake()->optional(0.5)->address(),
        ];
    }
}
