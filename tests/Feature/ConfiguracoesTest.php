<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can access configuracoes index and see all 6 cards', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get(route('configuracoes.index'))
        ->assertStatus(200)
        ->assertViewIs('configuracoes.index')
        ->assertSee('Tipos de Movimentos')
        ->assertSee('Tipos de Responsáveis')
        ->assertSee('Tipos de Equipes')
        ->assertSee('Tipos de Restrições')
        ->assertSee('Perfil de Usuário')
        ->assertSee('Importar Planilhas');
});

test('especialista can access configuracoes index and only see the 6th card (Importar Planilhas)', function () {
    $espec = User::factory()->create(['role' => 'espec']);
    $this->actingAs($espec)
        ->get(route('configuracoes.index'))
        ->assertStatus(200)
        ->assertViewIs('configuracoes.index')
        ->assertDontSee('Tipos de Movimentos')
        ->assertDontSee('Tipos de Responsáveis')
        ->assertDontSee('Tipos de Equipes')
        ->assertDontSee('Tipos de Restrições')
        ->assertDontSee('Perfil de Usuário')
        ->assertSee('Importar Planilhas');
});

test('coordenador cannot access configuracoes index', function () {
    $coord = User::factory()->create(['role' => 'coord']);
    $this->actingAs($coord)
        ->get(route('configuracoes.index'))
        ->assertStatus(403);
});

test('comum user cannot access configuracoes index', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user)
        ->get(route('configuracoes.index'))
        ->assertStatus(403);
});
