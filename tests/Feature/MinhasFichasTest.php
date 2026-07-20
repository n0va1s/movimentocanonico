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

describe('Minhas Fichas Export', function () {
    test('visitor can export standard CSV', function () {
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $this->actingAs($visitorUser);

        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('exportar')
            ->assertOk();
    });

    test('admin can export admin CSV', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Cria uma ficha vinculada ao evento
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'nom_candidato' => 'Candidato Teste Export',
            'tel_candidato' => '61999999999',
            'tip_situacao' => TipoSituacao::SELECIONADA,
        ]);

        // Cria a relação FichaVem com dados de pai, mãe e responsável
        \App\Models\FichaVem::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'nom_pai' => 'Pai do Candidato',
            'tel_pai' => '61988888888',
            'nom_mae' => 'Mae do Candidato',
            'tel_mae' => '61977777777',
            'nom_responsavel' => 'Responsavel Falar Com',
            'tel_responsavel' => '61966666666',
        ]);

        // Testa o método de exportação via Volt
        $response = Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('exportarAdmin')
            ->assertOk();

        // Recupera a StreamedResponse gerada do componente
        $streamedResponse = $response->instance()->exportarAdmin();
        
        ob_start();
        $streamedResponse->sendContent();
        $content = ob_get_clean();

        // Garante que o cabeçalho e os valores estejam presentes no CSV
        expect($content)
            ->toContain('Nome do Pai')
            ->toContain('Telefone do Pai')
            ->toContain('Nome da Mãe')
            ->toContain('Telefone da Mãe')
            ->toContain('Falar com (Nome)')
            ->toContain('Falar com (Telefone)')
            ->toContain('Candidato Teste Export')
            ->toContain('Pai do Candidato')
            ->toContain('Mae do Candidato');
    });

    test('visitor cannot export admin CSV', function () {
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $this->actingAs($visitorUser);

        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('exportarAdmin')
            ->assertForbidden();
    });
});

describe('Minhas Fichas Designator Removal and Dropdown Mod', function () {
    test('dropdown retorna todos os visitadores incluindo aqueles com 3 ou mais fichas e possui contagem correta', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        // Atribuir 3 fichas para o visitador
        Ficha::factory()->count(3)->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        $component = Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])->call('loadData');
        
        $visitadores = $component->get('visitadores');
        
        // O visitador com 3 fichas deve estar na coleção retornado pelo backend
        $matched = $visitadores->firstWhere('idt_pessoa', $visitorPessoa->idt_pessoa);
        expect($matched)->not->toBeNull();
        expect($matched->ficha_count)->toBe(3);
    });

    test('coordenador ou administrador consegue limpar a designacao de uma unica ficha', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('loadData')
            ->call('limparDesignacao', $ficha->idt_ficha)
            ->assertHasNoErrors();

        expect($ficha->fresh()->idt_pessoa_visitacao)->toBeNull();
    });


    test('visitador sem permissao nao consegue limpar a designacao', function () {
        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Visitador comum tenta limpar sua própria ficha
        $this->actingAs($visitorUser);

        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('loadData')
            ->call('limparDesignacao', $ficha->idt_ficha)
            ->assertStatus(403);
            
        expect($ficha->fresh()->idt_pessoa_visitacao)->toBe($visitorPessoa->idt_pessoa);
    });

    test('deve ser possivel buscar casal pelo nome do parceiro no model Pessoa', function () {
        $partner1 = Pessoa::factory()->create([
            'nom_pessoa' => 'Adriano Silva',
            'nom_apelido' => 'Adri',
            'idt_usuario' => null
        ]);

        $partner2 = Pessoa::factory()->create([
            'nom_pessoa' => 'Beatriz Santos',
            'nom_apelido' => 'Bia',
            'idt_usuario' => null,
            'idt_parceiro' => $partner1->idt_pessoa
        ]);
        
        $partner1->update(['idt_parceiro' => $partner2->idt_pessoa]);

        // Buscar parceiro 1 pelo nome do parceiro 2
        $result = Pessoa::searchByName('Beatriz')->get();
        
        expect($result->contains('idt_pessoa', $partner1->idt_pessoa))->toBeTrue();
        expect($result->contains('idt_pessoa', $partner2->idt_pessoa))->toBeTrue();
    });

    test('deve ser possivel filtrar fichas sem visitador designado', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitorUser = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorPessoa = $visitorUser->pessoa;

        // Ficha com visitador
        $fichaComVisitador = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => $visitorPessoa->idt_pessoa,
            'nom_candidato' => 'Candidato Com Visitador',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        // Ficha sem visitador
        $fichaSemVisitador = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
            'idt_pessoa_visitacao' => null,
            'nom_candidato' => 'Candidato Sem Visitador',
            'tip_situacao' => TipoSituacao::SELECIONADA
        ]);

        Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('loadData')
            ->assertSee('Candidato Com Visitador')
            ->assertSee('Candidato Sem Visitador')
            ->set('apenasSemDesignacao', true)
            ->assertSee('Candidato Sem Visitador')
            ->assertDontSee('Candidato Com Visitador');
    });

    test('deve ser possivel filtrar visitadores no modal pelo termo de busca', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $visitorUserA = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorUserA->pessoa->update(['nom_pessoa' => 'Adriano Silva']);

        $visitorUserB = createVisitorUser(['idt_movimento' => 2], $this->eventoVem);
        $visitorUserB->pessoa->update(['nom_pessoa' => 'Beatriz Santos']);

        $component = Volt::test('minhas-fichas.index', ['evento' => $this->eventoVem])
            ->call('loadData')
            ->set('modalSearch', 'Adriano');

        $visitadores = $component->get('visitadores');
        expect($visitadores->contains('idt_pessoa', $visitorUserA->pessoa->idt_pessoa))->toBeTrue();
        expect($visitadores->contains('idt_pessoa', $visitorUserB->pessoa->idt_pessoa))->toBeFalse();
    });
});

