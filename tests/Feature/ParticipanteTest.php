<?php

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    createMovimentos();
    $this->movimento = TipoMovimento::first();
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
    
    // Create an event
    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
    ]);
});

// ==========================================
// TESTES DO COMPONENTE PARTICIPANTES (VOLT)
// ==========================================

test('usuario pode renderizar componente de participantes', function () {
    $this->actingAs($this->admin);

    Participante::factory()->count(3)->create([
        'idt_evento' => $this->evento->idt_evento,
    ]);

    Volt::test('evento.partials.participantes', ['evento' => $this->evento])
        ->assertSet('evento', $this->evento)
        ->assertSee('Participantes Confirmados');
});

test('usuario pode buscar participantes por nome no componente', function () {
    $this->actingAs($this->admin);

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

test('usuario pode atualizar cor de troca de participante', function () {
    $this->actingAs($this->admin);

    $pessoa = Pessoa::factory()->create(['nom_pessoa' => 'Luiz Silva', 'nom_apelido' => 'Lula']);
    $participante = Participante::factory()->create([
        'idt_evento' => $this->evento->idt_evento,
        'idt_pessoa' => $pessoa->idt_pessoa,
        'tip_cor_troca' => 'azul',
    ]);

    Volt::test('evento.partials.participantes', ['evento' => $this->evento])
        ->call('atualizarTroca', $participante->idt_participante, 'verde')
        ->assertDispatched('notify');

    expect($participante->fresh()->tip_cor_troca)->toBe('verde');
});
