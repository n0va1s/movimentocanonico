<?php

use App\Enums\TipoEvento;
use App\Mail\BoasVindasMail;
use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    createMovimentos();
    $this->evento = Evento::factory()->create([
        'idt_movimento' => TipoMovimento::VEM, 'tip_evento' => TipoEvento::ENCONTRO,
        'dat_inicio' => '2026-11-26', 'ind_candidatura_aberta' => true,
    ]);
    $this->equipes = TipoEquipe::factory()->count(2)->create([
        'idt_movimento' => TipoMovimento::VEM, 'ind_disponivel_candidatura' => true,
    ]);
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('botão e formulário público dependem de um encontro aberto', function () {
    $lista = route('trabalhadores.candidatura.eventos');
    $formulario = route('trabalhadores.candidatura', $this->evento);
    $this->get($lista)->assertSee('Quero trabalhar')->assertSee($this->evento->des_evento);

    $this->evento->update(['ind_candidatura_aberta' => false]);
    $this->get($lista)->assertDontSee('Quero trabalhar')->assertDontSee($this->evento->des_evento);
    $this->get($formulario)->assertNotFound();
    $this->post($formulario)->assertNotFound();
});

test('candidatura pública cria conta temporária e entra na triagem sem duplicar', function () {
    Mail::fake();
    $equipe = $this->equipes->first()->getKey();
    $url = route('trabalhadores.candidatura', $this->evento);
    $this->get($url)->assertOk()->assertSee('Enviar candidatura')->assertSee('Tipo de candidatura')->assertSee('Jovem')->assertSee('Casal');
    $dados = [
        'nome' => 'Voluntária de teste', 'email' => 'voluntaria@example.test', 'cpf' => '529.982.247-25',
        'equipes' => [$equipe => ['selecionado' => '1', 'habilidade' => 'Posso organizar materiais.']],
        'toca_instrumento' => '1', 'instrumento' => 'Violão',
    ];
    $this->from($url)->post($url, $dados)->assertSessionHasErrors('tipo_candidatura');
    $this->from($url)->post($url, $dados + ['tipo_candidatura' => 'outro'])->assertSessionHasErrors('tipo_candidatura');
    $dados['tipo_candidatura'] = 'casal';
    $this->from($url)->post($url, $dados)->assertSessionHasErrors(['nome_conjuge', 'telefone_conjuge', 'email_conjuge']);
    $dados += ['nome_conjuge' => 'Pessoa Cônjuge', 'telefone_conjuge' => '(61) 99999-1234', 'email_conjuge' => 'conjuge@example.test'];
    $this->from($url)->post($url, $dados)->assertSessionHas('candidatura_enviada');
    $this->from($url)->post($url, $dados)->assertSessionHas('candidatura_enviada');
    $pessoa = Pessoa::where('num_cpf_pessoa', '52998224725')->sole();
    $this->assertDatabaseHas('candidatura_voluntario', [
        'idt_pessoa' => $pessoa->getKey(), 'tipo_candidatura' => 'casal', 'nome_conjuge' => 'Pessoa Cônjuge',
        'apelido_conjuge' => null, 'telefone_conjuge' => '61999991234', 'email_conjuge' => 'conjuge@example.test',
    ]);
    expect($pessoa->voluntarios()->count())->toBe(1)
        ->and($pessoa->usuario->senha_temporaria_expira_em)->not->toBeNull();
    Mail::assertQueued(BoasVindasMail::class, 1);
    Volt::actingAs($this->admin)->test('evento.partials.voluntarios', ['evento' => $this->evento])
        ->assertSee('Voluntária de teste')->assertSee('Violão')->assertSee('Casal')->assertSee('conjuge@example.test')
        ->call('naoSelecionar', $pessoa->getKey())->assertHasErrors('motivo')
        ->set('motivo', 'Equipe completa')->call('naoSelecionar', $pessoa->getKey())->assertHasNoErrors()
        ->call('escolherSituacao', 'recusado')->assertSee('Equipe completa');
});

test('origem é trocável e a escalação anterior fica no histórico', function () {
    $primeira = Evento::factory()->create(['idt_movimento' => TipoMovimento::VEM, 'tip_evento' => TipoEvento::ENCONTRO, 'dat_inicio' => '2026-08-01']);
    $segunda = Evento::factory()->create(['idt_movimento' => TipoMovimento::SGM, 'tip_evento' => TipoEvento::ENCONTRO, 'dat_inicio' => '2026-09-01']);
    $pessoa = createPessoa();
    $outra = createPessoa();
    $primeira->participantes()->create(['idt_pessoa' => $pessoa->getKey()]);
    $segunda->participantes()->create(['idt_pessoa' => $outra->getKey()]);

    Volt::actingAs($this->admin)->test('evento.partials.voluntarios', ['evento' => $this->evento])
        ->call('editarOrigem')->assertSet('alterandoOrigem', true)->assertSee($primeira->des_evento)
        ->set('origem', (string) $primeira->getKey())->call('vincularOrigem')
        ->assertSet('alterandoOrigem', false)->assertSee('Ex-venista')
        ->set('equipe', (string) $this->equipes->first()->getKey())->call('escalar', $pessoa->getKey())
        ->set('origem', (string) $segunda->getKey())->call('vincularOrigem')->assertSee('Ex-seguimista')
        ->call('escolherSituacao', 'escalado')->assertSee($pessoa->nom_pessoa);
    expect((int) $this->evento->fresh()->idt_encontro_origem)->toBe($segunda->getKey());
});

test('triagem pagina candidatos e busca por nome sem acento em todas as páginas', function () {
    foreach (range(1, 22) as $numero) {
        $pessoa = Pessoa::create([
            'nom_pessoa' => $numero === 22 ? 'Última Candidata' : sprintf('Candidato %02d', $numero),
            'eml_pessoa' => "triagem-$numero@example.test",
        ]);
        $pessoa->voluntarios()->create([
            'idt_evento' => $this->evento->getKey(),
            'idt_equipe' => $this->equipes->first()->getKey(),
        ]);
    }

    Volt::actingAs($this->admin)->test('evento.gerenciamento', ['evento' => $this->evento])
        ->assertSee('Resumo')->call('setTab', 'voluntarios')->assertSee('Triagem de voluntários');

    Volt::actingAs($this->admin)->test('evento.partials.voluntarios', ['evento' => $this->evento])
        ->assertSee('Aguardando escalação (22)')->assertSee('Candidato 20')->assertDontSee('Última Candidata')
        ->call('setPage', 2, 'paginaVoluntarios')->assertSee('Última Candidata')->assertDontSee('Candidato 20')
        ->set('busca', 'ultima')->assertSee('Última Candidata')->assertDontSee('Candidato 21');
});

test('senha temporária obriga a troca antes de acessar o painel', function () {
    $usuario = User::factory()->create(['role' => User::ROLE_USER]);
    $usuario->forceFill(['senha_temporaria_expira_em' => now()->addDay()])->save();
    Volt::test('auth.login')->set('email', $usuario->email)->set('password', 'password')
        ->call('login')->assertRedirect(route('primeira-senha.edit'));
    $this->get(route('dashboard'))->assertRedirect(route('primeira-senha.edit'));
    $this->get(route('primeira-senha.edit'))->assertOk();
    $this->post(route('primeira-senha.update'), [
        'current_password' => 'password',
        'password' => 'Senha-nova-de-teste',
        'password_confirmation' => 'Senha-nova-de-teste',
    ])->assertRedirect(route('dashboard'));
    expect($usuario->fresh()->senha_temporaria_expira_em)->toBeNull()
        ->and(Hash::check('Senha-nova-de-teste', $usuario->fresh()->password))->toBeTrue();
});

test('acompanhamento conta trabalhadores por equipe e inclui equipes vazias', function () {
    [$primeira, $segunda] = $this->equipes->all();
    $vazia = TipoEquipe::factory()->create(['idt_movimento' => $this->evento->idt_movimento]);
    $pessoa = createPessoa();

    foreach ([$primeira, $segunda] as $indice => $equipe) {
        Trabalhador::create([
            'idt_evento' => $this->evento->getKey(),
            'idt_pessoa' => $pessoa->getKey(),
            'idt_equipe' => $equipe->getKey(),
            'ind_coordenador' => $indice === 0,
        ]);
    }

    $resumo = app(\App\Services\TriagemVoluntarios::class)
        ->resumoEscalacao($this->evento, TipoEquipe::catalogo($this->evento->idt_movimento));

    expect($resumo['total'])->toBe(1)
        ->and($resumo['equipes'][$primeira->getKey()]['escalados'])->toBe(1)
        ->and($resumo['equipes'][$primeira->getKey()]['coordenadores'])->toBe(1)
        ->and($resumo['equipes'][$segunda->getKey()]['escalados'])->toBe(1)
        ->and($resumo['equipes'][$vazia->getKey()]['escalados'])->toBe(0);

    Volt::actingAs($this->admin)->test('evento.partials.voluntarios', ['evento' => $this->evento])
        ->set('acompanhandoEscalacao', true)->assertSee('Acompanhamento da escalação')
        ->assertSee($vazia->des_grupo)->assertHasNoErrors();
});

test('aba de trabalhadores altera alocação e envia a avaliação ao quadrante', function () {
    [$primeira, $segunda] = $this->equipes->all();
    $pessoa = createPessoa();
    $trabalhador = Trabalhador::create([
        'idt_evento' => $this->evento->getKey(),
        'idt_pessoa' => $pessoa->getKey(),
        'idt_equipe' => $primeira->getKey(),
    ]);

    Volt::actingAs($this->admin)->test('evento.partials.trabalhadores', ['evento' => $this->evento])
        ->assertSee($pessoa->nom_pessoa)->set('visualizacao', 'equipes')->assertSee($primeira->des_grupo)
        ->assertSet('equipeAbertaId', '')->set('equipeAbertaId', (string) $primeira->getKey())->assertSee($pessoa->nom_pessoa)
        ->set('situacaoAvaliacao', 'pendentes')->call('abrirAlterarEquipe', $trabalhador->getKey())
        ->set('alterarEquipeId', $segunda->getKey())->call('salvarAlterarEquipe')->assertHasNoErrors()
        ->set('equipeAbertaId', (string) $segunda->getKey())
        ->call('abrirAvaliacao', $trabalhador->getKey())->set('formAvaliacao.ind_recomendado', true)
        ->call('salvarAvaliacao')->assertHasNoErrors()
        ->assertViewHas('dadosTrabalhadores', fn ($dados) => $dados['equipes'][$segunda->getKey()]['itens'] === [])
        ->set('situacaoAvaliacao', 'avaliados')->assertSee($pessoa->nom_pessoa)->assertSee('Avaliado');

    expect((int) $trabalhador->fresh()->idt_equipe)->toBe($segunda->getKey())
        ->and($trabalhador->fresh()->ind_avaliacao)->toBeTrue();
});

test('coordenação pode ser indicada sem cadastro e vinculada sem duplicar a pessoa', function () {
    [$primeira, $segunda] = $this->equipes->all();
    $pessoa = createPessoa();
    Volt::actingAs($this->admin)->test('evento.partials.coordenadores', ['evento' => $this->evento])
        ->call('abrir', $primeira->getKey())->set('busca', 'Pessoa sem cadastro')
        ->call('semCadastro')->set('telefone', '(61) 90000-0001')->call('salvar')
        ->assertSee('Cadastro pendente')->assertSee('Pedir cadastro pelo WhatsApp')
        ->call('abrir', $primeira->getKey(), true)->call('vincular', $pessoa->getKey())
        ->assertSee('Coordenador vinculado')
        ->call('abrir', $segunda->getKey())->call('vincular', $pessoa->getKey())
        ->assertHasErrors('vinculo');
    expect(Trabalhador::where('idt_pessoa', $pessoa->getKey())->where('ind_coordenador', true)->count())->toBe(1);
    Volt::actingAs($this->admin)->test('evento.partials.voluntarios', ['evento' => $this->evento])
        ->set('coordenadores', true)->assertSee('Indicação de coordenadores')
        ->assertSee('Fechar painel')
        ->call('escolherSituacao', 'escalado')->assertSee($pessoa->nom_pessoa);
});
