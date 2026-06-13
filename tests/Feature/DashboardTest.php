<?php

use App\Models\Evento;
use App\Models\Ficha;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
});

test('visitante sao direcionados pra pagina de login', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('usuario autenticados acessam o dashboard', function () {
    $this->actingAs($this->user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('dashboard exibe contadores corretos', function () {
    $movimento = TipoMovimento::factory()->create();

    // Criar eventos
    $eventos = Evento::factory()->count(3)->create([
        'idt_movimento' => $movimento->idt_movimento,
        'dat_inicio' => now()->addDays(1),
        'dat_termino' => now()->addDays(2),
    ]);

    // Criar fichas vinculadas a um dos eventos criados
    Ficha::factory()->count(5)->create([
        'idt_evento' => $eventos->first()->idt_evento,
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('qtdEventosAtivos', 3);
    $response->assertViewHas('qtdFichasCadastradas', 5);
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

test('dashboard conta trabalhadores unicos', function () {
    $movimento = TipoMovimento::factory()->create();
    $evento1 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $evento2 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $pessoa = Pessoa::factory()->create();

    // Criar múltiplos trabalhadores para a mesma pessoa em eventos diferentes
    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
    ]);

    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento2->idt_evento,
    ]);

    $this->actingAs($this->user);
    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertViewHas('qtdTrabalhadoresCadastrados', 1); // Deve contar apenas 1 pessoa única
});
