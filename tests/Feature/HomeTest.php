<?php

use App\Models\Contato;
use App\Models\Evento;
use App\Models\TipoMovimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Não colocar no BeforeEach para evitar autenticação em testes que não precisam
function autenticarUsuarioComPessoa($test)
{
    $user = User::factory()->create(['role' => 'user']);
    $test->actingAs($user);

    return $user;
}

// ==========================================
// TESTES DE PÁGINA INICIAL
// ==========================================
test('visitante pode acessar pagina inicial', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertViewIs('welcome');
});

test('pagina inicial exibe informacoes da organizacao', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Movimento Canônico');
    $response->assertSee('Não tenhais medo!');
});

test('pagina inicial exibe movimentos', function () {
    // garante que existem movimentos no banco
    TipoMovimento::factory()->create(['nom_movimento' => 'VEM']);
    TipoMovimento::factory()->create(['nom_movimento' => 'Segue-Me']);
    TipoMovimento::factory()->create(['nom_movimento' => 'ECC']);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('VEM');
    $response->assertSee('Segue-Me');
    $response->assertSee('ECC');
});

// ==========================================
// TESTES DE REDIRECIONAMENTOS
// ==========================================

test('visitante nao autenticado permanece na home', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertViewIs('welcome');
});

// ==========================================
// TESTES DE SEO E META TAGS
// ==========================================

test('pagina inicial possui titulo correto', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('<title>Movimento Canônico</title>', false);
});

// ==========================================
// TESTES DE RESPONSIVIDADE
// ==========================================

test('pagina inicial e responsiva', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('viewport', false);
});

// ==========================================
// CONTATO
// ==========================================

test('visitante pode enviar formulario de contato com dados validos', function () {
    $movimento = TipoMovimento::factory()->create();

    $response = $this->post(route('home.contato'), [
        'nom_contato' => 'João da Silva',
        'eml_contato' => 'joao@email.com',
        'tel_contato' => '61999999999',
        'txt_mensagem' => 'Mensagem de teste',
        'idt_movimento' => $movimento->idt_movimento,
    ]);

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('contato', [
        'nom_contato' => 'João da Silva',
        'tel_contato' => '61999999999',
    ]);
});

test('formulario de contato exige campos obrigatorios', function () {
    $response = $this->post(route('home.contato'), []);

    $response->assertSessionHasErrors([
        'nom_contato',
        'tel_contato',
        'txt_mensagem',
        'idt_movimento',
    ]);
});

// ==========================================
// FICHAS
// ==========================================

test('visitante se autentica e pode acessar formulario publico de ficha VEM', function () {
    autenticarUsuarioComPessoa($this);

    TipoMovimento::factory()->create([
        'idt_movimento' => TipoMovimento::VEM,
    ]);

    Evento::factory()->count(2)->create([
        'idt_movimento' => TipoMovimento::VEM,
    ]);

    $response = $this->get(route('home.ficha.vem'));

    $response
        ->assertStatus(200)
        ->assertViewIs('ficha.formVEM')
        ->assertViewHas('ficha')
        ->assertViewHas('eventos')
        ->assertViewHas('movimentopadrao', TipoMovimento::VEM);
});

test('visitante se autentica e pode acessar formulario publico de ficha ECC', function () {
    autenticarUsuarioComPessoa($this);

    $response = $this->get(route('home.ficha.ecc'));

    $response
        ->assertStatus(200)
        ->assertViewIs('ficha.formECC');
});

test('visitante se autentica e pode acessar formulario publico de ficha SGM', function () {
    autenticarUsuarioComPessoa($this);

    $response = $this->get(route('home.ficha.sgm'));

    $response
        ->assertStatus(200)
        ->assertViewIs('ficha.formSGM');
});

test('visitante nao autenticado nao pode acessar ficha VEM', function () {
    $response = $this->get(route('home.ficha.vem'));

    $response->assertRedirect(route('login'));
});

test('visitante nao autenticado nao pode acessar ficha ECC', function () {
    $response = $this->get(route('home.ficha.ecc'));

    $response->assertRedirect(route('login'));
});

test('visitante nao autenticado nao pode acessar ficha Segue-Me', function () {
    $response = $this->get(route('home.ficha.sgm'));

    $response->assertRedirect(route('login'));
});
