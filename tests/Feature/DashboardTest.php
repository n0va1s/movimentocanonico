<?php

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
});

test('dashboard exibe proximos eventos', function () {
    $movimento = TipoMovimento::factory()->create();

    $evento = Evento::factory()->create([
        'idt_movimento' => $movimento->idt_movimento,
        'des_evento' => 'Evento Futuro',
        'dat_inicio' => now()->addDays(1),
        'dat_termino' => now()->addDays(2),
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('proximoseventos');
    $response->assertSee('Evento Futuro');
});

test('dashboard conta participantes unicos', function () {
    $movimento = TipoMovimento::factory()->create();
    $evento1 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $evento2 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $pessoa = Pessoa::factory()->create();

    // Criar múltiplos participantes para a mesma pessoa em eventos diferentes
    Participante::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
    ]);

    Participante::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento2->idt_evento,
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('qtdParticipantesCadastrados', 1); // Deve contar apenas 1 pessoa única
});

test('dashboard exibe aniversariantes da semana', function () {
    // Aniversário hoje (esta semana)
    $pessoaHoje = Pessoa::factory()->create([
        'nom_pessoa' => 'Aniversariante Hoje',
        'nom_apelido' => null,
        'dat_nascimento' => now()->subYears(25),
    ]);

    // Aniversário na próxima semana (fora da semana atual)
    $pessoaProximaSemana = Pessoa::factory()->create([
        'nom_pessoa' => 'Aniversariante Futuro',
        'nom_apelido' => null,
        'dat_nascimento' => now()->startOfWeek()->addDays(8)->subYears(25),
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('aniversariantes');
    
    $aniversariantes = $response->viewData('aniversariantes');
    expect($aniversariantes->pluck('idt_pessoa'))->toContain($pessoaHoje->idt_pessoa);
    expect($aniversariantes->pluck('idt_pessoa'))->not->toContain($pessoaProximaSemana->idt_pessoa);
});

test('dashboard exibe lideres de aura', function () {
    $pessoaLider = Pessoa::factory()->create([
        'nom_pessoa' => 'Lider Aura',
        'nom_apelido' => null,
        'qtd_pontos_total' => 1500,
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('lideresAura');
    $response->assertSee('Lider Aura');
});

test('dashboard exibe formulario de contato e envia com sucesso', function () {
    $movimento = TipoMovimento::factory()->create();

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Entre em Contato');
    $response->assertSee('nom_contato');
    $response->assertSee($movimento->des_sigla);

    // Enviar formulário com o referer apontando para /dashboard (usando de '/dashboard')
    $postResponse = $this->from('/dashboard')->post(route('home.contato'), [
        'nom_contato' => 'João da Silva',
        'eml_contato' => 'joao@email.com',
        'tel_contato' => '61999999999',
        'txt_mensagem' => 'Dúvida sobre o próximo evento',
        'idt_movimento' => $movimento->idt_movimento,
    ]);

    $postResponse->assertRedirect(route('dashboard'));
    $postResponse->assertSessionHas('success');
});
