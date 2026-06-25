<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\User;
use App\Models\Voluntario;
use App\Services\VoluntarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Mockery;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SETUP GLOBAL
|--------------------------------------------------------------------------
*/
beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->pessoa = Pessoa::factory()->create([
        'idt_usuario' => $this->user->id,
    ]);

    $this->evento = Evento::factory()->create();

    $this->equipe1 = TipoEquipe::factory()->create(['idt_movimento' => $this->evento->idt_movimento]);
    $this->equipe2 = TipoEquipe::factory()->create(['idt_movimento' => $this->evento->idt_movimento]);
    $this->equipe3 = TipoEquipe::factory()->create(['idt_movimento' => $this->evento->idt_movimento]);
    $this->equipe4 = TipoEquipe::factory()->create(['idt_movimento' => $this->evento->idt_movimento]);
});

afterEach(fn () => Mockery::close());

function makeValidPayload($overrides = [])
{
    return array_replace_recursive([
        'idt_evento' => test()->evento->idt_evento,
        'equipes' => [
            test()->equipe1->idt_equipe => [
                'selecionado' => '1',
                'habilidade' => 'Habilidade válida equipe 1',
            ],
            test()->equipe3->idt_equipe => [
                'selecionado' => '1',
                'habilidade' => 'Habilidade válida equipe 3',
            ],
        ],
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| CREATE / STORE
|--------------------------------------------------------------------------
*/
describe('Candidatura', function () {

    test('usuario se candidata com sucesso', function () {
        $this->app->instance(VoluntarioService::class, new VoluntarioService);

        $payload = makeValidPayload();

        $this->actingAs($this->user)
            ->post(route('trabalhadores.store'), $payload)
            ->assertRedirect(route('eventos.index', ['evento' => $payload['idt_evento']]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('voluntario', 2);

        $this->assertDatabaseHas('voluntario', [
            'idt_equipe' => $this->equipe1->idt_equipe,
        ]);
    });

    test('Candidatura → falha se duplicar candidatura', function () {
        $payload = makeValidPayload();

        $this->actingAs($this->user)->post(route('trabalhadores.store'), $payload);
        $this->actingAs($this->user)->post(route('trabalhadores.store'), $payload);

        $this->assertDatabaseCount('voluntario', 2);
    });

    test('falha candidatura sem equipes selecionadas', function () {
        $this->actingAs($this->user)
            ->post(route('trabalhadores.store'), [
                'idt_evento' => $this->evento->idt_evento,
                'equipes' => [],
            ])
            ->assertSessionHasErrors();
    });
});


/*
|--------------------------------------------------------------------------
| VOLUNTARIO (MODEL)
|--------------------------------------------------------------------------
*/
describe('Voluntario model', function () {

    test('relacionamentos basicos funcionam corretamente', function () {
        $voluntario = Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
        ]);

        expect($voluntario->pessoa)->toBeInstanceOf(Pessoa::class);
        expect($voluntario->evento)->toBeInstanceOf(Evento::class);
        expect($voluntario->equipe)->toBeInstanceOf(TipoEquipe::class);
    });

    test('listarAgrupadoPorPessoa retorna voluntarios agrupados e sem trabalhador', function () {
        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe2->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe3->idt_equipe,
            'idt_trabalhador' => Trabalhador::factory()->create()->idt_trabalhador,
        ]);

        $resultado = Voluntario::listarAgrupadoPorPessoa($this->evento->idt_evento);

        expect($resultado)->toHaveCount(1);
        expect($resultado->first()->equipes)->toHaveCount(2);
    });

    test('listarEquipesSelecionadas retorna apenas equipes do evento e pessoa informados', function () {
        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe1->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe2->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe3->idt_equipe,
            'idt_trabalhador' => Trabalhador::factory()->create()->idt_trabalhador,
        ]);

        $equipes = Voluntario::listarEquipesSelecionadas(
            $this->evento->idt_evento,
            $this->pessoa->idt_pessoa
        );

        expect($equipes)->toHaveCount(2);
    });

    test('listarAgrupadoPorPessoa retorna vazio quando nao ha voluntarios', function () {
        $resultado = Voluntario::listarAgrupadoPorPessoa($this->evento->idt_evento);
        expect($resultado)->toBeEmpty();
    });
});
