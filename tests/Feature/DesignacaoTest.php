<?php

use App\Models\User;
use App\Models\Ficha;
use App\Models\Pessoa;
use App\Models\Evento;
use App\Models\TipoMovimento;
use App\Enums\TipoSituacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::table('tipo_movimento')->insertOrIgnore([
        ['idt_movimento' => 1, 'nom_movimento' => 'Encontro de Casais com Cristo', 'des_sigla' => 'ECC', 'dat_inicio' => '1980-01-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 2, 'nom_movimento' => 'Encontro de Adolescentes com Cristo', 'des_sigla' => 'VEM', 'dat_inicio' => '2000-07-01', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->eventoVem = Evento::factory()->create([
        'idt_movimento' => 2,
        'dat_inicio' => now()->addDays(1)->format('Y-m-d'),
        'dat_termino' => now()->addDays(4)->format('Y-m-d'),
    ]);
});

describe('Designação Volt Component', function () {
    test('renders successfully for admin', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Volt::test('evento.partials.designacao', ['evento' => $this->eventoVem])
            ->assertStatus(200);
    });

    test('lists candidates with their correct situation/status badge', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $fichaContato = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Contato',
            'tip_situacao' => TipoSituacao::CONTATO,
        ]);

        $fichaSelecionada = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Selecionado',
            'tip_situacao' => TipoSituacao::SELECIONADA,
        ]);

        Volt::test('evento.partials.designacao', ['evento' => $this->eventoVem])
            ->assertSee('Candidato Contato')
            ->assertSee('Contato')
            ->assertSee('Candidato Selecionado')
            ->assertSee('Selecionada');
    });

    test('updates visitor designation inline', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitor = User::factory()->create(['role' => 'visit']);
        $visitorPessoa = $visitor->pessoa;

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Teste',
            'tip_situacao' => TipoSituacao::SELECIONADA,
            'idt_pessoa_visitacao' => null,
        ]);

        Volt::test('evento.partials.designacao', ['evento' => $this->eventoVem])
            ->call('atualizarVisitador', $ficha->idt_ficha, $visitorPessoa->idt_pessoa)
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        expect($ficha->fresh()->idt_pessoa_visitacao)->toBe($visitorPessoa->idt_pessoa);
    });

    test('filters candidates by visitador', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitor1 = User::factory()->create(['role' => 'visit']);
        $visitorPessoa1 = $visitor1->pessoa;

        $visitor2 = User::factory()->create(['role' => 'visit']);
        $visitorPessoa2 = $visitor2->pessoa;

        $ficha1 = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Um',
            'tip_situacao' => TipoSituacao::SELECIONADA,
            'idt_pessoa_visitacao' => $visitorPessoa1->idt_pessoa,
        ]);

        $ficha2 = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Dois',
            'tip_situacao' => TipoSituacao::SELECIONADA,
            'idt_pessoa_visitacao' => $visitorPessoa2->idt_pessoa,
        ]);

        $fichaSem = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Sem',
            'tip_situacao' => TipoSituacao::SELECIONADA,
            'idt_pessoa_visitacao' => null,
        ]);

        Volt::test('evento.partials.designacao', ['evento' => $this->eventoVem])
            ->assertSee('Candidato Um')
            ->assertSee('Candidato Dois')
            ->assertSee('Candidato Sem')
            ->set('visitadorFiltro', (string)$visitorPessoa1->idt_pessoa)
            ->assertSee('Candidato Um')
            ->assertDontSee('Candidato Dois')
            ->assertDontSee('Candidato Sem')
            ->set('visitadorFiltro', 'sem')
            ->assertSee('Candidato Sem')
            ->assertDontSee('Candidato Um')
            ->assertDontSee('Candidato Dois');
    });
});
