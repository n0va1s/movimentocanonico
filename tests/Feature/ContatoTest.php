<?php

use App\Models\Contato;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
    Pessoa::factory()->for($this->user, 'usuario')->create();
    createMovimentos();
});

describe('Listagem de contatos', function () {
    test('a página de listagem de contatos está acessível e mostra os dados', function () {
        $contatos = Contato::factory()->count(3)->create();

        $response = actingAs($this->user)->get(route('contatos.index'));
        $response->assertStatus(200)
            ->assertViewIs('contato.list');

        foreach ($contatos as $contato) {
            $response->assertSee($contato->nom_contato);
        }
    });
});

describe('Busca de contatos', function () {
    test('a busca por contatos funciona corretamente', function () {
        // Cria um contato que será encontrado pela busca
        Contato::factory()->create(['nom_contato' => 'Contato para busca']);

        // Cria contatos que não devem aparecer no resultado da busca
        Contato::factory()->create(['nom_contato' => 'Outro contato qualquer']);

        $response = actingAs($this->user)->get(route('contatos.index', ['search' => 'busca']));

        $response->assertStatus(200)
            ->assertViewIs('contato.list')
            ->assertSee('Contato para busca')
            ->assertDontSee('Outro contato qualquer');
    });
});

describe('Resolução de contatos', function () {
    test('pode resolver (soft delete) um contato com sucesso', function () {
        $contato = Contato::factory()->create();

        actingAs($this->user)
            ->delete(route('contatos.destroy', $contato->idt_contato))
            ->assertRedirect(route('contatos.index'))
            ->assertSessionHas('success', 'Contato resolvido com sucesso!');

        // Verifica se o registro foi "soft-deleted"
        $this->assertSoftDeleted('contato', ['idt_contato' => $contato->idt_contato]);
    });

    test('retorna 404 ao tentar resolver um contato inexistente', function () {
        actingAs($this->user)
            ->delete(route('contatos.destroy', 999))
            ->assertStatus(404);
    });
});
