<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SETUP GLOBAL
|--------------------------------------------------------------------------
*/
beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->user = User::factory()->create([
        'role' => 'user',
    ]);
});

/*
|--------------------------------------------------------------------------
| LISTAGEM DE PERFIS
|--------------------------------------------------------------------------
*/
describe('TipoPerfilController::index', function () {

    test('admin consegue acessar a lista de perfis', function () {
        $this->actingAs($this->admin)
            ->get(route('role.index'))
            ->assertStatus(200)
            ->assertViewIs('configuracoes.TipoPerfilList')
            ->assertViewHas('perfis');
    });

    test('admin consegue buscar perfis por nome, perfil e movimento', function () {
        $movimento1 = \App\Models\TipoMovimento::factory()->create(['nom_movimento' => 'Movimento Um', 'des_sigla' => 'M1']);
        $movimento2 = \App\Models\TipoMovimento::factory()->create(['nom_movimento' => 'Movimento Dois', 'des_sigla' => 'M2']);

        $user1 = User::factory()->create([
            'name' => 'Ana Silva',
            'role' => 'coord',
            'idt_movimento' => $movimento1->idt_movimento,
        ]);

        $user2 = User::factory()->create([
            'name' => 'Bruno Costa',
            'role' => 'user',
            'idt_movimento' => $movimento2->idt_movimento,
        ]);

        // Busca por nome
        $response = $this->actingAs($this->admin)
            ->get(route('role.index', ['nome' => 'Ana']))
            ->assertStatus(200);
        
        $perfisExibidos = $response->viewData('perfis');
        expect($perfisExibidos->pluck('id'))->toContain($user1->id);
        expect($perfisExibidos->pluck('id'))->not->toContain($user2->id);

        // Busca por perfil
        $response = $this->actingAs($this->admin)
            ->get(route('role.index', ['perfil' => 'user']))
            ->assertStatus(200);
        
        $perfisExibidos = $response->viewData('perfis');
        expect($perfisExibidos->pluck('id'))->toContain($user2->id);
        expect($perfisExibidos->pluck('id'))->not->toContain($user1->id);

        // Busca por movimento
        $response = $this->actingAs($this->admin)
            ->get(route('role.index', ['movimento' => $movimento1->idt_movimento]))
            ->assertStatus(200);
        
        $perfisExibidos = $response->viewData('perfis');
        expect($perfisExibidos->pluck('id'))->toContain($user1->id);
        expect($perfisExibidos->pluck('id'))->not->toContain($user2->id);

        // Busca por sem movimento (none)
        $response = $this->actingAs($this->admin)
            ->get(route('role.index', ['movimento' => 'none']))
            ->assertStatus(200);
        
        $perfisExibidos = $response->viewData('perfis');
        expect($perfisExibidos->pluck('id'))->toContain($this->admin->id);
        expect($perfisExibidos->pluck('id'))->toContain($this->user->id);
        expect($perfisExibidos->pluck('id'))->not->toContain($user1->id);
    });

    test('usuario comum nao pode acessar a lista de perfis', function () {
        $this->actingAs($this->user)
            ->get(route('role.index'))
            ->assertForbidden();
    });
});

/*
|--------------------------------------------------------------------------
| ALTERAÇÃO DE PERFIL
|--------------------------------------------------------------------------
*/
describe('TipoPerfilController::change', function () {

    test('admin consegue alterar o perfil e o movimento de um usuario', function () {
        $movimento = \App\Models\TipoMovimento::factory()->create();
        $usuarioAlvo = User::factory()->create([
            'role' => 'user',
            'idt_movimento' => null,
        ]);

        $this->actingAs($this->admin)
            ->from(route('role.index'))
            ->post(route('role.change'), [
                'role' => [
                    $usuarioAlvo->id => 'coord',
                ],
                'movimento' => [
                    $usuarioAlvo->id => $movimento->idt_movimento,
                ],
            ])
            ->assertRedirect(route('role.index'))
            ->assertSessionHas('success');

        expect($usuarioAlvo->fresh()->role)->toBe('coord');
        expect($usuarioAlvo->fresh()->idt_movimento)->toBe($movimento->idt_movimento);
    });

    test('admin consegue alterar o perfil para dirig e selecionar um movimento', function () {
        $movimento = \App\Models\TipoMovimento::factory()->create();
        $usuarioAlvo = User::factory()->create([
            'role' => 'user',
            'idt_movimento' => null,
        ]);

        $this->actingAs($this->admin)
            ->from(route('role.index'))
            ->post(route('role.change'), [
                'role' => [
                    $usuarioAlvo->id => 'dirig',
                ],
                'movimento' => [
                    $usuarioAlvo->id => $movimento->idt_movimento,
                ],
            ])
            ->assertRedirect(route('role.index'))
            ->assertSessionHas('success');

        expect($usuarioAlvo->fresh()->role)->toBe('dirig');
        expect($usuarioAlvo->fresh()->idt_movimento)->toBe($movimento->idt_movimento);
    });

    test('admin consegue limpar o movimento de um usuario', function () {
        $movimento = \App\Models\TipoMovimento::factory()->create();
        $usuarioAlvo = User::factory()->create([
            'role' => 'coord',
            'idt_movimento' => $movimento->idt_movimento,
        ]);

        $this->actingAs($this->admin)
            ->from(route('role.index'))
            ->post(route('role.change'), [
                'role' => [
                    $usuarioAlvo->id => 'coord',
                ],
                'movimento' => [
                    $usuarioAlvo->id => '',
                ],
            ])
            ->assertRedirect(route('role.index'))
            ->assertSessionHas('success');

        expect($usuarioAlvo->fresh()->role)->toBe('coord');
        expect($usuarioAlvo->fresh()->idt_movimento)->toBeNull();
    });

    test('nao permite alterar para um perfil invalido', function () {
        $this->actingAs($this->admin)
            ->post(route('role.change'), [
                'role' => [
                    $this->user->id => 'perfil_invalido',
                ],
            ])
            ->assertSessionHasErrors('role.*');
    });
});


