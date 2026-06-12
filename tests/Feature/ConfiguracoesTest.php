<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can access configuracoes index and see all authorized cards', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)
        ->get(route('configuracoes.index'))
        ->assertStatus(200)
        ->assertViewIs('configuracoes.index')
        ->assertSee('Tipos de Equipes')
        ->assertSee('Perfil de Usuário')
        ->assertSee('Limpar Cache')
        ->assertSee('Otimizar Tudo')
        ->assertSee('Storage Link')
        ->assertSee('Encerrar Eventos')
        ->assertSee('Importar Planilhas')
        ->assertSee('Fichas VEM')
        ->assertSee('Fichas SGM')
        ->assertSee('Fichas ECC');
});

test('especialista can access configuracoes index and only see the 4 authorized cards', function () {
    $espec = User::factory()->create(['role' => 'espec']);
    $this->actingAs($espec)
        ->get(route('configuracoes.index'))
        ->assertStatus(200)
        ->assertViewIs('configuracoes.index')
        ->assertDontSee('Tipos de Equipes')
        ->assertDontSee('Perfil de Usuário')
        ->assertSee('Importar Planilhas')
        ->assertSee('Fichas VEM')
        ->assertSee('Fichas SGM')
        ->assertSee('Fichas ECC');
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
