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

test('admin can access create team type page and see movements select option', function () {
    createMovimentos();
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)
        ->get(route('equipe.create'))
        ->assertStatus(200)
        ->assertSee('Cadastrar Tipo de Equipe')
        ->assertSee('Encontro de Casais com Cristo')
        ->assertSee('Encontro de Jovens com Cristo');
});

test('admin can store a new team type with movement select option', function () {
    createMovimentos();
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)
        ->post(route('equipe.store'), [
            'des_grupo' => 'Equipe de Teste Nova',
            'idt_movimento' => 2, // VEM
        ]);

    $response->assertRedirect(route('equipe.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tipo_equipe', [
        'des_grupo' => 'Equipe de Teste Nova',
        'idt_movimento' => 2,
    ]);
});

test('admin cannot store team type without idt_movimento', function () {
    createMovimentos();
    $admin = User::factory()->create(['role' => 'admin']);
    
    $response = $this->actingAs($admin)
        ->post(route('equipe.store'), [
            'des_grupo' => 'Equipe de Teste Invalida',
        ]);

    $response->assertSessionHasErrors(['idt_movimento']);
});

test('admin can access edit team type page and update it', function () {
    createMovimentos();
    $admin = User::factory()->create(['role' => 'admin']);
    
    $equipe = \App\Models\TipoEquipe::create([
        'des_grupo' => 'Equipe Original',
        'idt_movimento' => 1,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('equipe.edit', $equipe->idt_equipe))
        ->assertStatus(200)
        ->assertSee('Editar Tipo de Equipe')
        ->assertSee('value="1" selected', false);

    $responseUpdate = $this->actingAs($admin)
        ->put(route('equipe.update', $equipe->idt_equipe), [
            'des_grupo' => 'Equipe Modificada',
            'idt_movimento' => 3, // Segue-Me
        ]);

    $responseUpdate->assertRedirect(route('equipe.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tipo_equipe', [
        'idt_equipe' => $equipe->idt_equipe,
        'des_grupo' => 'Equipe Modificada',
        'idt_movimento' => 3,
    ]);
});
