<?php

use App\Enums\TipoEvento;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Models\User;
use App\Models\Voluntario;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

// ─────────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    createMovimentos();

    $this->movimento = TipoMovimento::first();

    // Usuários por perfil
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->coord = User::factory()->create(['role' => 'coord']);
    $this->espec = User::factory()->create([
        'role' => 'espec',
        'idt_movimento' => $this->movimento->idt_movimento,
    ]);
    $this->user = User::factory()->create(['role' => 'user']);

    // Pessoas vinculadas
    $pessoaAdmin = $this->admin->pessoa;
    $pessoaCoord = $this->coord->pessoa;
    $pessoaEspec = $this->espec->pessoa;
    $pessoaUser = $this->user->pessoa;

    // Evento do tipo ENCONTRO (habilita todas as abas)
    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'tip_evento' => TipoEvento::ENCONTRO->value,
        'des_evento' => 'Encontro de Gerenciamento',
    ]);

    $this->equipe = TipoEquipe::first();

    // Vincula coord e espec como trabalhadores do evento para que passem nos Gates
    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoaCoord->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'idt_equipe' => $this->equipe->idt_equipe,
        'ind_coordenador' => true,
        'ind_presente' => false,
    ]);

    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoaEspec->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'idt_equipe' => $this->equipe->idt_equipe,
        'ind_presente' => false,
    ]);
});



// ─────────────────────────────────────────────────────────────────────────────
// Prestação de Contas — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Prestação de Contas — contas.blade.php', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);
    });

    test('saveFinanceiro persiste val_receita, val_despesa e txt_relatorio', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valReceita', '1500.00')
            ->set('valDespesa', '800.00')
            ->set('txtRelatorio', 'Relatório do evento de teste.')
            ->call('saveFinanceiro')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evento', [
            'idt_evento' => $this->evento->idt_evento,
            'val_receita' => 1500.00,
            'val_despesa' => 800.00,
            'txt_relatorio' => 'Relatório do evento de teste.',
        ]);
    });

    test('mount pré-carrega valores existentes do evento', function () {
        $this->evento->update([
            'val_receita' => 2000.00,
            'val_despesa' => 1200.00,
            'txt_relatorio' => 'Relatório existente.',
        ]);

        $component = Volt::test('evento.partials.contas', ['evento' => $this->evento->fresh()]);

        $component
            ->assertSet('valReceita', 2000.00)
            ->assertSet('valDespesa', 1200.00)
            ->assertSet('txtRelatorio', 'Relatório existente.');
    });

    test('val_receita negativo falha validação', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valReceita', '-100')
            ->call('saveFinanceiro')
            ->assertHasErrors(['valReceita']);
    });

    test('val_despesa negativo falha validação', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valDespesa', '-50')
            ->call('saveFinanceiro')
            ->assertHasErrors(['valDespesa']);
    });

    test('val_receita não numérico falha validação', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valReceita', 'abc')
            ->call('saveFinanceiro')
            ->assertHasErrors(['valReceita']);
    });

    test('txt_relatorio acima de 3000 caracteres falha validação', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('txtRelatorio', str_repeat('a', 3001))
            ->call('saveFinanceiro')
            ->assertHasErrors(['txtRelatorio']);
    });

    test('campos opcionais nulos são aceitos', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valReceita', null)
            ->set('valDespesa', null)
            ->set('txtRelatorio', null)
            ->call('saveFinanceiro')
            ->assertHasNoErrors();
    });

    test('saveFinanceiro dispara evento notify de sucesso', function () {
        Volt::test('evento.partials.contas', ['evento' => $this->evento])
            ->set('valReceita', '500')
            ->set('valDespesa', '200')
            ->call('saveFinanceiro')
            ->assertDispatched('notify');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Voluntários → Trabalhadores — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Voluntários — confirmarTrabalhador', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);
        $this->pessoaVol = createPessoa();

        // Voluntário pendente
        $this->voluntario = Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
            'idt_trabalhador' => null,
        ]);
    });

    test('confirmarTrabalhador cria Trabalhador e vincula voluntário', function () {
        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa)
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('trabalhador', [
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
        ]);

        expect(
            Voluntario::where('idt_pessoa', $this->pessoaVol->idt_pessoa)
                ->where('idt_evento', $this->evento->idt_evento)
                ->whereNull('idt_trabalhador')
                ->count()
        )->toBe(0);
    });

    test('confirmarTrabalhador sem equipe selecionada dispara notify de erro', function () {
        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('trabalhador', [
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
        ]);
    });

    test('confirmarTrabalhador duas vezes não cria duplicata', function () {
        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa);

        // Segunda chamada — já existe trabalhador
        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa)
            ->assertDispatched('notify');

        expect(
            Trabalhador::where('idt_pessoa', $this->pessoaVol->idt_pessoa)
                ->where('idt_evento', $this->evento->idt_evento)
                ->count()
        )->toBe(1);
    });

    test('confirmarTrabalhador vincula todos os voluntários pendentes da pessoa', function () {
        $equipe2 = TipoEquipe::skip(1)->first();

        // Segunda candidatura da mesma pessoa
        Voluntario::factory()->create([
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $equipe2->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa);

        $pendentes = Voluntario::where('idt_pessoa', $this->pessoaVol->idt_pessoa)
            ->where('idt_evento', $this->evento->idt_evento)
            ->whereNull('idt_trabalhador')
            ->count();

        expect($pendentes)->toBe(0);
    });

    test('confirmarTrabalhador como admin definindo coordenação e primeira vez', function () {
        Volt::test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->set("indCoordenador.{$this->pessoaVol->idt_pessoa}", true)
            ->set("indPrimeiraVez.{$this->pessoaVol->idt_pessoa}", true)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('trabalhador', [
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
            'ind_coordenador' => true,
            'ind_primeira_vez' => true,
        ]);
    });

    test('coordenador do evento pode confirmar voluntário', function () {
        Volt::actingAs($this->coord)
            ->test('evento.partials.voluntarios', ['evento' => $this->evento])
            ->set("selectedEquipes.{$this->pessoaVol->idt_pessoa}", $this->equipe->idt_equipe)
            ->call('confirmarTrabalhador', $this->pessoaVol->idt_pessoa)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('trabalhador', [
            'idt_pessoa' => $this->pessoaVol->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Presença — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Presença — togglePresenca', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);

        $this->pessoaP = createPessoa();
        $this->pessoaT = createPessoa();

        $this->participante = Participante::factory()->create([
            'idt_pessoa' => $this->pessoaP->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'ind_presente' => false,
        ]);

        $this->trabalhador = Trabalhador::factory()->create([
            'idt_pessoa' => $this->pessoaT->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
            'ind_presente' => false,
        ]);
    });

    test('togglePresenca inverte ind_presente de Participante para true', function () {
        Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->call('togglePresenca', $this->participante->idt_participante, 'participante');

        expect($this->participante->fresh()->ind_presente)->toBeTrue();
    });

    test('togglePresenca inverte ind_presente de Participante de volta para false', function () {
        $this->participante->update(['ind_presente' => true]);

        Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->call('togglePresenca', $this->participante->idt_participante, 'participante');

        expect($this->participante->fresh()->ind_presente)->toBeFalse();
    });

    test('togglePresenca inverte ind_presente de Trabalhador para true', function () {
        Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->call('togglePresenca', $this->trabalhador->idt_trabalhador, 'trabalhador');

        expect($this->trabalhador->fresh()->ind_presente)->toBeTrue();
    });

    test('togglePresenca inverte ind_presente de Trabalhador de volta para false', function () {
        $this->trabalhador->update(['ind_presente' => true]);

        Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->call('togglePresenca', $this->trabalhador->idt_trabalhador, 'trabalhador');

        expect($this->trabalhador->fresh()->ind_presente)->toBeFalse();
    });

    test('lista retorna participantes e trabalhadores quando filtroTipo é todos', function () {
        $component = Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->set('filtroTipo', 'todos');

        $lista = $component->get('lista');

        $tipos = collect($lista)->pluck('tipo')->unique()->sort()->values()->all();
        expect($tipos)->toContain('participante')->toContain('trabalhador');
    });

    test('lista retorna apenas participantes quando filtroTipo é participantes', function () {
        $component = Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->set('filtroTipo', 'participantes');

        $lista = $component->get('lista');

        expect(collect($lista)->pluck('tipo')->unique()->all())->toBe(['participante']);
    });

    test('lista retorna apenas trabalhadores quando filtroTipo é trabalhadores', function () {
        $component = Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->set('filtroTipo', 'trabalhadores');

        $lista = $component->get('lista');

        expect(collect($lista)->pluck('tipo')->unique()->all())->toBe(['trabalhador']);
    });

    test('busca por nome filtra corretamente', function () {
        $pessoaEspecifica = Pessoa::factory()->create(['nom_pessoa' => 'Zacarias Único']);
        Participante::factory()->create([
            'idt_pessoa' => $pessoaEspecifica->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'ind_presente' => false,
        ]);

        $component = Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->set('filtroTipo', 'participantes')
            ->set('search', 'Zacarias');

        $lista = $component->get('lista');

        expect(count($lista))->toBe(1)
            ->and($lista[0]['nome'])->toBe('Zacarias Único');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Quadrante — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Quadrante — filtro por presença', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);

        $this->pessoaPresente = createPessoa();
        $this->pessoaAusente = createPessoa();
        $this->trabPresente = createPessoa();
        $this->trabAusente = createPessoa();

        Participante::factory()->create([
            'idt_pessoa' => $this->pessoaPresente->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'ind_presente' => true,
            'tip_cor_troca' => 'azul',
        ]);

        Participante::factory()->create([
            'idt_pessoa' => $this->pessoaAusente->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'ind_presente' => false,
            'tip_cor_troca' => 'verde',
        ]);

        Trabalhador::factory()->create([
            'idt_pessoa' => $this->trabPresente->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
            'ind_presente' => true,
        ]);

        Trabalhador::factory()->create([
            'idt_pessoa' => $this->trabAusente->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'idt_equipe' => $this->equipe->idt_equipe,
            'ind_presente' => false,
        ]);
    });

    test('totalParticipantes conta apenas presentes', function () {
        $component = Volt::test('evento.partials.quadrante', ['evento' => $this->evento]);

        expect($component->get('totalParticipantes'))->toBe(1);
    });

    test('totalTrabalhadores conta apenas presentes', function () {
        $component = Volt::test('evento.partials.quadrante', ['evento' => $this->evento]);

        expect($component->get('totalTrabalhadores'))->toBe(1);
    });

    test('participantes retorna apenas os com ind_presente true', function () {
        $component = Volt::test('evento.partials.quadrante', ['evento' => $this->evento]);

        $participantes = $component->get('participantes');
        $ids = $participantes->flatten(1)->pluck('idt_pessoa')->all();

        expect($ids)->toContain($this->pessoaPresente->idt_pessoa)
            ->not->toContain($this->pessoaAusente->idt_pessoa);
    });

    test('trabalhadores retorna apenas os com ind_presente true', function () {
        $component = Volt::test('evento.partials.quadrante', ['evento' => $this->evento]);

        $trabalhadores = $component->get('trabalhadores');
        $ids = $trabalhadores->flatten(1)->pluck('idt_pessoa')->all();

        expect($ids)->toContain($this->trabPresente->idt_pessoa)
            ->not->toContain($this->trabAusente->idt_pessoa);
    });

    test('quando nenhum presente, totais são zero', function () {
        // Remove todos os presentes
        Participante::where('idt_evento', $this->evento->idt_evento)->update(['ind_presente' => false]);
        Trabalhador::where('idt_evento', $this->evento->idt_evento)->update(['ind_presente' => false]);

        $component = Volt::test('evento.partials.quadrante', ['evento' => $this->evento->fresh()]);

        expect($component->get('totalParticipantes'))->toBe(0)
            ->and($component->get('totalTrabalhadores'))->toBe(0);
    });

    test('marcar presença na aba Presença reflete no Quadrante', function () {
        // Participante começa ausente
        $novaPessoa = createPessoa();
        $novoParticipante = Participante::factory()->create([
            'idt_pessoa' => $novaPessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'ind_presente' => false,
        ]);

        $totalAntes = Volt::test('evento.partials.quadrante', ['evento' => $this->evento->fresh()])
            ->get('totalParticipantes');

        // Marca presença via componente de presença
        Volt::test('evento.partials.presenca', ['evento' => $this->evento])
            ->call('togglePresenca', $novoParticipante->idt_participante, 'participante');

        $totalDepois = Volt::test('evento.partials.quadrante', ['evento' => $this->evento->fresh()])
            ->get('totalParticipantes');

        expect($totalDepois)->toBe($totalAntes + 1);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Participantes — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Participantes — participantes.blade.php', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);

        $this->pessoaP = createPessoa();

        $this->participante = Participante::factory()->create([
            'idt_pessoa' => $this->pessoaP->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
            'tip_cor_troca' => 'azul',
        ]);
    });

    test('usuario pode buscar participantes por nome no componente', function () {
        $pessoa1 = Pessoa::factory()->create(['nom_pessoa' => 'João da Silva']);
        $pessoa2 = Pessoa::factory()->create(['nom_pessoa' => 'Maria Souza']);

        Participante::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'idt_pessoa' => $pessoa1->idt_pessoa,
        ]);

        Participante::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'idt_pessoa' => $pessoa2->idt_pessoa,
        ]);

        Volt::test('evento.partials.participantes', ['evento' => $this->evento])
            ->set('search', 'João')
            ->assertSee('João da Silva')
            ->assertDontSee('Maria Souza');
    });

    test('atualizarTroca muda tip_cor_troca e dispara notify', function () {
        Volt::test('evento.partials.participantes', ['evento' => $this->evento])
            ->call('atualizarTroca', $this->participante->idt_participante, 'verde')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        expect($this->participante->fresh()->tip_cor_troca)->toBe('verde');
    });

    test('excluirParticipante remove participante e dispara notify', function () {
        Volt::test('evento.partials.participantes', ['evento' => $this->evento])
            ->call('excluirParticipante', $this->participante->idt_participante)
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('participante', [
            'idt_participante' => $this->participante->idt_participante,
        ]);
    });

    test('exportar executa com sucesso', function () {
        Volt::test('evento.partials.participantes', ['evento' => $this->evento])
            ->call('exportar')
            ->assertOk();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Restrições de Saúde — componente Livewire Volt
// ─────────────────────────────────────────────────────────────────────────────

describe('Restrições de Saúde — restricoes.blade.php', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);
    });

    test('exibe as restricoes de saude dos participantes e trabalhadores do evento', function () {
        $evento = $this->evento;
        
        $restricao = \App\Models\TipoRestricao::factory()->create(['tip_restricao' => 'ALE', 'des_restricao' => 'Amendoim']);
        $pessoa1 = \App\Models\Pessoa::factory()->create(['nom_pessoa' => 'Maria Alérgica']);
        $pessoa1->restricoes()->attach($restricao->idt_restricao, ['txt_complemento' => 'Evitar contato']);
        
        \App\Models\Participante::factory()->create([
            'idt_pessoa' => $pessoa1->idt_pessoa,
            'idt_evento' => $evento->idt_evento,
            'tip_cor_troca' => 'azul',
        ]);

        $pessoaTrab = \App\Models\Pessoa::factory()->create(['nom_pessoa' => 'José Trabalhador']);
        $pessoaTrab->restricoes()->attach($restricao->idt_restricao, ['txt_complemento' => 'Leve']);
        
        \App\Models\Trabalhador::factory()->create([
            'idt_pessoa' => $pessoaTrab->idt_pessoa,
            'idt_evento' => $evento->idt_evento,
        ]);

        $ficha = \App\Models\Ficha::factory()->create([
            'idt_evento' => $evento->idt_evento,
            'nom_candidato' => 'Pedro Pendente',
            'tip_situacao' => \App\Enums\TipoSituacao::NOVA,
        ]);
        $ficha->fichaSaude()->create([
            'idt_restricao' => $restricao->idt_restricao,
            'txt_complemento' => 'Leve',
        ]);

        Volt::test('evento.partials.restricoes', ['evento' => $evento])
            ->assertSee('Maria Alérgica')
            ->assertSee('José Trabalhador')
            ->assertSee('Amendoim')
            ->assertDontSee('Pedro Pendente');
    });
});

describe('Gerenciamento de Evento — Abas e Mais Opções', function () {
    test('exibe abas exclusivas para evento do tipo ENCONTRO', function () {
        $this->actingAs($this->admin);
        
        Volt::test('evento.gerenciamento', ['evento' => $this->evento])
            ->assertSee("setTab('fichas')", false);
    });

    test('não exibe abas exclusivas para evento de outro tipo', function () {
        $this->actingAs($this->admin);
        
        $eventoDesafio = Evento::factory()->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'tip_evento' => TipoEvento::DESAFIO->value,
            'des_evento' => 'Desafio Teste',
        ]);
        
        Volt::test('evento.gerenciamento', ['evento' => $eventoDesafio])
            ->assertDontSee("setTab('fichas')", false);
    });

    test('não exibe aba do mercadinho', function () {
        $this->actingAs($this->admin);
        
        Volt::test('evento.gerenciamento', ['evento' => $this->evento])
            ->assertDontSee("setTab('mercadinho')", false);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Fichas — componente Livewire Volt (Dashboard e Filtros)
// ─────────────────────────────────────────────────────────────────────────────

describe('Fichas — fichas.blade.php', function () {

    beforeEach(function () {
        $this->actingAs($this->admin);

        // Limpa fichas antigas para evitar poluição nos testes
        \App\Models\Ficha::where('idt_evento', $this->evento->idt_evento)->delete();

        // Cria algumas fichas com diferentes situações
        $this->fichaNova = \App\Models\Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => \App\Enums\TipoSituacao::NOVA,
            'nom_candidato' => 'Candidato Novo',
        ]);

        $this->fichaAguardando = \App\Models\Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => \App\Enums\TipoSituacao::AGUARDANDO,
            'nom_candidato' => 'Candidato Aguardando',
        ]);
        
        $this->fichaAprovada = \App\Models\Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => \App\Enums\TipoSituacao::APROVADA,
            'nom_candidato' => 'Candidato Aprovado',
        ]);
    });

    test('exibe a contagem correta de cada situação no dashboard', function () {
        $component = Volt::test('evento.partials.fichas', ['evento' => $this->evento]);
        
        $contadores = $component->get('contadores');
        
        expect($contadores[\App\Enums\TipoSituacao::NOVA->value])->toBe(1)
            ->and($contadores[\App\Enums\TipoSituacao::AGUARDANDO->value])->toBe(1)
            ->and($contadores[\App\Enums\TipoSituacao::APROVADA->value])->toBe(1)
            ->and($contadores[\App\Enums\TipoSituacao::CANCELADA->value])->toBe(0);
    });

    test('clicar em um card de status filtra a listagem de fichas', function () {
        $component = Volt::test('evento.partials.fichas', ['evento' => $this->evento]);
        
        // Inicialmente mostra todas
        $component->assertSee('Candidato Novo')
            ->assertSee('Candidato Aguardando')
            ->assertSee('Candidato Aprovado');

        // Filtra por novas
        $component->call('toggleFiltroSituacao', \App\Enums\TipoSituacao::NOVA->value)
            ->assertSee('Candidato Novo')
            ->assertDontSee('Candidato Aguardando')
            ->assertDontSee('Candidato Aprovado');
            
        expect($component->get('filtroSituacao'))->toBe(\App\Enums\TipoSituacao::NOVA->value);
    });

    test('clicar novamente no card ativo remove o filtro', function () {
        $component = Volt::test('evento.partials.fichas', ['evento' => $this->evento])
            ->call('toggleFiltroSituacao', \App\Enums\TipoSituacao::NOVA->value)
            ->assertDontSee('Candidato Aprovado');

        // Clica de novo no mesmo status
        $component->call('toggleFiltroSituacao', \App\Enums\TipoSituacao::NOVA->value)
            ->assertSee('Candidato Novo')
            ->assertSee('Candidato Aprovado');
            
        expect($component->get('filtroSituacao'))->toBeNull();
    });


});


