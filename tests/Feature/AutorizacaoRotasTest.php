<?php

use App\Models\Pessoa;
use App\Models\User;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function userComRole(string $role): User
{
    $user = User::factory()->create(['role' => $role]);

    // Garante que a pessoa vinculada existe (criada pelo observer do User)
    return $user;
}

// ---------------------------------------------------------------------------
// Rotas públicas (sem autenticação)
// ---------------------------------------------------------------------------

test('home é acessível sem autenticação', function () {
    $this->get('/')->assertStatus(200);
});

// ---------------------------------------------------------------------------
// Redirecionamento de guest para rotas protegidas
// ---------------------------------------------------------------------------

$rotasAuth = [
    '/dashboard',
    '/vem',
    '/ecc',
    '/sgm',
    '/timeline',
    '/termo-sgm',
    '/termo-vem',
    '/trabalhadores/create',
    '/eventos',
    '/settings/profile',
    '/settings/password',
    '/settings/appearance',
    '/eventos/importar',
];

foreach ($rotasAuth as $rota) {
    test("guest é redirecionado em {$rota}", function () use ($rota) {
        $this->get($rota)->assertRedirect();
    });
}

// ---------------------------------------------------------------------------
// Rotas somente ADMIN — outros perfis recebem 403
// ---------------------------------------------------------------------------

$rotasAdmin = [
    '/contatos',
    '/configuracoes/role',
    '/configuracoes/equipe',
    '/eventos/create',
    '/pessoas',
    '/pessoas/create',
    '/aniversario',
];

foreach ($rotasAdmin as $rota) {
    test("admin acessa {$rota}", function () use ($rota) {
        createMovimentos();
        $this->actingAs(userComRole('admin'))
            ->get($rota)
            ->assertStatus(200);
    });

    foreach (['coord', 'espec', 'user'] as $perfil) {
        test("{$perfil} recebe 403 em {$rota}", function () use ($rota, $perfil) {
            $this->actingAs(userComRole($perfil))
                ->get($rota)
                ->assertStatus(403);
        });
    }
}

// ---------------------------------------------------------------------------
// Rotas ADMIN e ESPEC — outros perfis recebem 403
// ---------------------------------------------------------------------------

$rotasAdminEspec = [
    '/configuracoes',
    '/eventos/importar',
    '/fichas/vem',
    '/fichas/vem/create',
    '/fichas/ecc',
    '/fichas/ecc/create',
    '/fichas/sgm',
    '/fichas/sgm/create',
];

foreach ($rotasAdminEspec as $rota) {
    test("admin acessa {$rota} (admin,espec)", function () use ($rota) {
        createMovimentos();
        $this->actingAs(userComRole('admin'))
            ->get($rota)
            ->assertStatus(200);
    });

    test("espec acessa {$rota} (admin,espec)", function () use ($rota) {
        createMovimentos();
        
        $idtMovimento = null;
        if (str_contains($rota, '/vem')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'VEM')->first()?->idt_movimento;
        } elseif (str_contains($rota, '/ecc')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'ECC')->first()?->idt_movimento;
        } elseif (str_contains($rota, '/sgm')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'Segue-Me')->first()?->idt_movimento;
        } else {
            $idtMovimento = \App\Models\TipoMovimento::first()?->idt_movimento;
        }

        $user = User::factory()->create([
            'role' => 'espec',
            'idt_movimento' => $idtMovimento
        ]);

        $this->actingAs($user)
            ->get($rota)
            ->assertStatus(200);
    });

    test("espec de outro movimento recebe 403 em {$rota}", function () use ($rota) {
        createMovimentos();
        
        $idtMovimento = null;
        if (str_contains($rota, '/vem')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'ECC')->first()?->idt_movimento;
        } elseif (str_contains($rota, '/ecc')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'Segue-Me')->first()?->idt_movimento;
        } elseif (str_contains($rota, '/sgm')) {
            $idtMovimento = \App\Models\TipoMovimento::where('des_sigla', 'VEM')->first()?->idt_movimento;
        }
        
        if ($idtMovimento) {
            $user = User::factory()->create([
                'role' => 'espec',
                'idt_movimento' => $idtMovimento
            ]);
            
            $this->actingAs($user)
                ->get($rota)
                ->assertStatus(403);
        }
    })->skip(function () use ($rota) {
        return !str_contains($rota, '/vem') && !str_contains($rota, '/ecc') && !str_contains($rota, '/sgm');
    });

    foreach (['coord', 'user'] as $perfil) {
        test("{$perfil} recebe 403 em {$rota} (admin,espec)", function () use ($rota, $perfil) {
            $this->actingAs(userComRole($perfil))
                ->get($rota)
                ->assertStatus(403);
        });
    }
}

// ---------------------------------------------------------------------------
// Rota /trabalhadores — admin e coord acessam, espec e user recebem 403
// ---------------------------------------------------------------------------

test('admin acessa /trabalhadores', function () {
    createMovimentos();
    $this->actingAs(userComRole('admin'))
        ->get('/trabalhadores')
        ->assertStatus(200);
});

test('coord acessa /trabalhadores', function () {
    createMovimentos();
    $this->actingAs(userComRole('coord'))
        ->get('/trabalhadores')
        ->assertStatus(200);
});

test('espec recebe 403 em /trabalhadores', function () {
    $this->actingAs(userComRole('espec'))
        ->get('/trabalhadores')
        ->assertStatus(403);
});

test('user recebe 403 em /trabalhadores', function () {
    $this->actingAs(userComRole('user'))
        ->get('/trabalhadores')
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// Rotas de listagem — todos os perfis autenticados acessam
// ---------------------------------------------------------------------------

$rotasListagem = [
    '/eventos',
    '/dashboard',
    '/timeline',
];

foreach ($rotasListagem as $rota) {
    foreach (['admin', 'coord', 'espec', 'user'] as $perfil) {
        test("{$perfil} acessa listagem {$rota}", function () use ($rota, $perfil) {
            createMovimentos();
            $this->actingAs(userComRole($perfil))
                ->get($rota)
                ->assertStatus(200);
        });
    }
}

// ---------------------------------------------------------------------------
// Gerenciamento de evento — guest redireciona, user recebe 403
// ---------------------------------------------------------------------------

test('guest é redirecionado em gerenciamento de evento', function () {
    createMovimentos();
    $evento = createEvento();
    $this->get("/eventos/{$evento->idt_evento}/gerenciamento")
        ->assertRedirect();
});

test('user recebe 403 em gerenciamento de evento', function () {
    createMovimentos();
    $evento = createEvento();
    $this->actingAs(userComRole('user'))
        ->get("/eventos/{$evento->idt_evento}/gerenciamento")
        ->assertStatus(403);
});

test('admin acessa gerenciamento de evento', function () {
    createMovimentos();
    $evento = createEvento();
    $this->actingAs(userComRole('admin'))
        ->get("/eventos/{$evento->idt_evento}/gerenciamento")
        ->assertStatus(200);
});

test('admin acessa gerenciamento de evento inativo/deletado', function () {
    createMovimentos();
    $evento = createEvento();
    $evento->delete();
    $this->actingAs(userComRole('admin'))
        ->get("/eventos/{$evento->idt_evento}/gerenciamento")
        ->assertStatus(200);
});

test('espec acessa gerenciamento de evento inativo/deletado do seu movimento', function () {
    createMovimentos();
    $evento = createEvento();
    $evento->delete();
    $user = User::factory()->create([
        'role' => 'espec',
        'idt_movimento' => $evento->idt_movimento
    ]);
    $this->actingAs($user)
        ->get("/eventos/{$evento->idt_evento}/gerenciamento")
        ->assertStatus(200);
});
