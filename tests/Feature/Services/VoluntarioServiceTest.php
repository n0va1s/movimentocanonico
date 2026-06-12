<?php

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Voluntario;
use App\Models\Trabalhador;
use App\Services\VoluntarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new VoluntarioService;
    $this->movimento = TipoMovimento::factory()->create();
    $this->pessoa = Pessoa::factory()->create();
    $this->evento = Evento::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);
    $this->equipe1 = TipoEquipe::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);
    $this->equipe2 = TipoEquipe::factory()->create(['idt_movimento' => $this->movimento->idt_movimento]);
});

describe('VoluntarioService', function () {
    test('pode realizar candidatura com sucesso', function () {
        $equipesData = [
            $this->equipe1->idt_equipe => [
                'selecionado' => '1',
                'habilidade' => 'Habilidade válida',
            ],
        ];

        $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa);

        $this->assertDatabaseHas('voluntario', [
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
            'txt_habilidade' => 'Habilidade válida',
        ]);
    });

    test('falha candidatura com habilidade curta', function () {
        $equipesData = [
            $this->equipe1->idt_equipe => [
                'selecionado' => '1',
                'habilidade' => 'Curto',
            ],
        ];

        expect(fn () => $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa))
            ->toThrow(ValidationException::class);
    });

    test('falha candidatura com caracteres repetidos', function () {
        $equipesData = [
            $this->equipe1->idt_equipe => [
                'selecionado' => '1',
                'habilidade' => 'Aaaaaa',
            ],
        ];

        expect(fn () => $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa))
            ->toThrow(ValidationException::class);
    });

    test('falha candidatura sem nenhuma equipe', function () {
        $equipesData = [];

        expect(fn () => $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa))
            ->toThrow(ValidationException::class);
    });

    test('pode confirmar voluntario', function () {
        $voluntario = Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
        ]);

        $this->service->confirmacao(
            $voluntario->idt_voluntario,
            $this->equipe1->idt_equipe,
            true, // coordenador
            true  // primeira vez
        );

        $this->assertDatabaseHas('trabalhador', [
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
            'ind_coordenador' => true,
            'ind_primeira_vez' => true,
        ]);

        $voluntario->refresh();
        expect($voluntario->idt_trabalhador)->not->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// VoluntarioService — gaps não cobertos por outros testes
// ─────────────────────────────────────────────────────────────────────────────

describe('VoluntarioService — gaps', function () {

    beforeEach(function () {
        $this->service = new VoluntarioService;
        $this->pessoa = createPessoa();
        $this->equipes = TipoEquipe::factory()->count(4)->create([
            'idt_movimento' => $this->movimento->idt_movimento,
        ]);
    });

    test('candidatura com mais de 3 equipes lança ValidationException', function () {
        $equipesData = [];
        foreach ($this->equipes->take(4) as $equipe) {
            $equipesData[$equipe->idt_equipe] = [
                'selecionado' => '1',
                'habilidade' => 'Habilidade válida para esta equipe',
            ];
        }

        expect(fn () => $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa))
            ->toThrow(ValidationException::class);
    });

    test('confirmacao com voluntário inexistente lança ValidationException', function () {
        expect(fn () => $this->service->confirmacao(99999, $this->equipes->first()->idt_equipe))
            ->toThrow(ValidationException::class);
    });

    test('confirmacao não duplica Trabalhador (updateOrCreate)', function () {
        $voluntario = Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipes->first()->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        $this->service->confirmacao($voluntario->idt_voluntario, $this->equipes->first()->idt_equipe);
        $this->service->confirmacao($voluntario->idt_voluntario, $this->equipes->first()->idt_equipe);

        expect(
            Trabalhador::where('idt_pessoa', $this->pessoa->idt_pessoa)
                ->where('idt_evento', $this->evento->idt_evento)
                ->count()
        )->toBe(1);
    });

    test('confirmacao vincula todos os voluntários pendentes da pessoa ao trabalhador', function () {
        // Pessoa candidatada a 2 equipes
        $vol1 = Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipes->get(0)->idt_equipe,
            'idt_trabalhador' => null,
        ]);
        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipes->get(1)->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        $this->service->confirmacao($vol1->idt_voluntario, $this->equipes->get(0)->idt_equipe);

        $pendentes = Voluntario::where('idt_pessoa', $this->pessoa->idt_pessoa)
            ->where('idt_evento', $this->evento->idt_evento)
            ->whereNull('idt_trabalhador')
            ->count();

        expect($pendentes)->toBe(0);
    });
});
