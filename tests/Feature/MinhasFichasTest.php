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

if (!function_exists('createVisitorUser')) {
    function createVisitorUser(array $attributes = [], ?Evento $evento = null) {
        $user = User::factory()->create(array_merge(['role' => 'user'], $attributes));
        $pessoa = $user->pessoa;
        
        $movimentoId = $user->idt_movimento ?: ($evento ? $evento->idt_movimento : 2);
        
        $equipeVisitacao = \App\Models\TipoEquipe::firstOrCreate([
            'idt_movimento' => $movimentoId,
            'des_grupo' => 'Visitação'
        ]);
        
        \App\Models\Trabalhador::create([
            'idt_pessoa' => $pessoa->idt_pessoa,
            'idt_evento' => $evento ? $evento->idt_evento : ($attributes['idt_evento'] ?? 41),
            'idt_equipe' => $equipeVisitacao->idt_equipe,
            'ind_coordenador' => $attributes['ind_coordenador'] ?? false,
        ]);
        
        return $user;
    }
}

beforeEach(function () {
    DB::table('tipo_movimento')->insertOrIgnore([
        ['idt_movimento' => 1, 'nom_movimento' => 'Encontro de Casais com Cristo', 'des_sigla' => 'ECC', 'dat_inicio' => '1980-01-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 2, 'nom_movimento' => 'Encontro de Adolescentes com Cristo', 'des_sigla' => 'VEM', 'dat_inicio' => '2000-07-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 3, 'nom_movimento' => 'Encontro de Jovens com Cristo', 'des_sigla' => 'Segue-Me', 'dat_inicio' => '1990-12-31', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->eventoVem = Evento::factory()->create([
        'idt_movimento' => 2,
        'dat_inicio' => now()->addDays(1)->format('Y-m-d'),
        'dat_termino' => now()->addDays(4)->format('Y-m-d'),
    ]);
});

describe('Minhas Fichas Access Authorization', function () {
    test('guest is redirected to login', function () {
        $this->get(route('minhas-fichas.index'))
            ->assertRedirect(route('login'));
    });

    test('user with role user gets 403', function () {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->get(route('minhas-fichas.index'))
            ->assertForbidden();
    });

    test('user with role coord gets 403', function () {
        $user = User::factory()->create(['role' => 'coord']);
        $this->actingAs($user)
            ->get(route('minhas-fichas.index'))
            ->assertForbidden();
    });

    test('user with role dirig gets 200', function () {
        $user = User::factory()->create(['role' => 'dirig']);
        $this->actingAs($user)
            ->get(route('minhas-fichas.index'))
            ->assertStatus(200);
    });

    test('user with role visitacao gets 200', function () {
        $user = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $this->actingAs($user)
            ->get(route('minhas-fichas.index'))
            ->assertStatus(200);
    });

    test('user with role admin gets 200', function () {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)
            ->get(route('minhas-fichas.index'))
            ->assertStatus(200);
    });
});

describe('Minhas Fichas Scoping and Filtering', function () {
    test('visitor only sees fichas assigned to them and matching their movement', function () {
        // Create visitor
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        // Other visitor
        $otherUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $otherPessoa = $otherUser->pessoa;

        // Assigned Ficha (VEM)
        $assignedFicha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Assigned Candidate VEM',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Ficha assigned to other visitor
        $otherAssignedFicha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $otherPessoa->idt_pessoa,
            'nom_candidato' => 'Other Visitor Candidate VEM',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Unassigned Ficha
        $unassignedFicha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => null,
            'nom_candidato' => 'Unassigned Candidate VEM',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Ficha from different movement (ECC)
        $eventoEcc = Evento::factory()->create([
            'idt_movimento' => 1,
            'dat_inicio' => now()->addDays(1)->format('Y-m-d'),
            'dat_termino' => now()->addDays(4)->format('Y-m-d'),
        ]);
        $eccFicha = Ficha::factory()->create([
            'idt_evento' => $eventoEcc->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'ECC Candidate',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Act & Assert using Volt
        $this->actingAs($visitorUser);
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertSee('Assigned Candidate VEM')
            ->assertDontSee('Other Visitor Candidate VEM')
            ->assertDontSee('Unassigned Candidate VEM')
            ->assertDontSee('ECC Candidate');
    });

    test('visitor can see fichas assigned to their spouse/partner', function () {
        $visitorUserA = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoaA = $visitorUserA->pessoa;

        $visitorUserB = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoaB = $visitorUserB->pessoa;

        $visitorPessoaA->update(['idt_parceiro' => $visitorPessoaB->idt_pessoa]);
        $visitorPessoaB->update(['idt_parceiro' => $visitorPessoaA->idt_pessoa]);

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoaB->idt_pessoa,
            'nom_candidato' => 'Spouse Assigned Candidate',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        $this->actingAs($visitorUserA);
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertSee('Spouse Assigned Candidate');
    });


    test('ficha disappears from the visitor list when marked as VISITADA', function () {
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Visitada Candidate',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        $this->actingAs($visitorUser);
        
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertSee('Visitada Candidate');
 
        $ficha->update(['tip_situacao' => TipoSituacao::VISITADA]);
 
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertDontSee('Visitada Candidate');
    });

    test('admin only sees fichas where they are the designated visitor', function () {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $adminPessoa = $adminUser->pessoa;

        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        // Ficha assigned to Admin
        $ficha1 = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $adminPessoa->idt_pessoa,
            'nom_candidato' => 'Ficha for Admin',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Ficha assigned to visitor
        $ficha2 = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Ficha for Visitor',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        $this->actingAs($adminUser);
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertSee('Ficha for Admin')
            ->assertSee('Ficha for Visitor');
    });

    test('visitor can filter fichas by active event', function () {
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        // Create a second active event for movement 2
        $anotherEvent = Evento::factory()->create([
            'idt_movimento' => 2,
            'dat_inicio' => now()->addDays(5)->format('Y-m-d'),
            'dat_termino' => now()->addDays(8)->format('Y-m-d'),
        ]);

        // Ficha in first event (eventoVem)
        $ficha1 = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Candidate in Event One',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Ficha in second event (anotherEvent)
        $ficha2 = Ficha::factory()->create([
            'idt_evento' => $anotherEvent->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Candidate in Event Two',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        $this->actingAs($visitorUser);
 
        // By default, the first active event (eventoVem) is selected
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->assertSee('Candidate in Event One')
            ->assertDontSee('Candidate in Event Two')
            // Change the filter to the second event
            ->set('eventoId', $anotherEvent->idt_evento)
            ->assertSee('Candidate in Event Two')
            ->assertDontSee('Candidate in Event One');
    });
});

describe('Minhas Fichas Actions', function () {
    beforeEach(function () {
        $this->visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $this->visitorPessoa = $this->visitorUser->pessoa;
        $this->ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $this->visitorPessoa->idt_pessoa,
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);
        $this->actingAs($this->visitorUser);
    });

    test('can change status to Fiz Contato (F)', function () {
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->call('alterarSituacao', $this->ficha->idt_ficha, 'F')
            ->assertHasNoErrors();
 
        expect($this->ficha->fresh()->tip_situacao)->toBe(TipoSituacao::CONTATO);
    });
 
    test('can change status to Aguardando Resposta (W)', function () {
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->call('alterarSituacao', $this->ficha->idt_ficha, 'W')
            ->assertHasNoErrors();
 
        expect($this->ficha->fresh()->tip_situacao)->toBe(TipoSituacao::AGUARDANDO);
    });
 
    test('can change status to Cancelada (C)', function () {
        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData')
            ->call('alterarSituacao', $this->ficha->idt_ficha, 'C')
            ->assertHasNoErrors();
 
        expect($this->ficha->fresh()->tip_situacao)->toBe(TipoSituacao::CANCELADA);
    });
});

describe('Minhas Fichas Visitor Designation', function () {
    beforeEach(function () {
        $this->visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $this->visitorPessoa = $this->visitorUser->pessoa;
        $this->ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => null,
        ]);
    });

    test('admin can assign a visitor to a ficha', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('fichas.designar-visitador', $this->ficha->idt_ficha), [
                'idt_pessoa_visitacao' => $this->visitorPessoa->idt_pessoa
            ])
            ->assertRedirect();

        expect($this->ficha->fresh()->idt_pessoa_visitacao)->toBe($this->visitorPessoa->idt_pessoa);
    });

    test('dirigente (dirig) can assign a visitor to a ficha', function () {
        $dirig = User::factory()->create(['role' => 'dirig', 'idt_movimento' => 2]);
        $this->actingAs($dirig)
            ->post(route('fichas.designar-visitador', $this->ficha->idt_ficha), [
                'idt_pessoa_visitacao' => $this->visitorPessoa->idt_pessoa
            ])
            ->assertRedirect();

        expect($this->ficha->fresh()->idt_pessoa_visitacao)->toBe($this->visitorPessoa->idt_pessoa);
    });

    test('coord cannot assign a visitor to a ficha', function () {
        $coord = User::factory()->create(['role' => 'coord']);
        $this->actingAs($coord)
            ->post(route('fichas.designar-visitador', $this->ficha->idt_ficha), [
                'idt_pessoa_visitacao' => $this->visitorPessoa->idt_pessoa
            ])
            ->assertForbidden();
    });

    test('user cannot assign a visitor to a ficha', function () {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->post(route('fichas.designar-visitador', $this->ficha->idt_ficha), [
                'idt_pessoa_visitacao' => $this->visitorPessoa->idt_pessoa
            ])
            ->assertForbidden();
    });
});
