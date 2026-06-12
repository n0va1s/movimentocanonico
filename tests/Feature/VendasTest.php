<?php

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\Conta;
use App\Models\Transacao;
use App\Models\Produto;
use App\Models\TipoMovimento;
use App\Models\User;
use App\Models\Participante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    createMovimentos();
    $this->movimento = TipoMovimento::first();
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'user']);
    
    // Associar pessoa ao usuário comum
    $this->pessoa = Pessoa::factory()->create([
        'idt_usuario' => $this->user->id,
        'nom_pessoa' => 'José da Silva',
        'eml_pessoa' => $this->user->email,
    ]);

    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'dat_termino' => now()->addDays(5)->format('Y-m-d'),
    ]);

    // Vincular pessoa ao evento como participante
    Participante::factory()->create([
        'idt_evento' => $this->evento->idt_evento,
        'idt_pessoa' => $this->pessoa->idt_pessoa,
    ]);
});

// ==========================================
// TESTES DE MODELAGEM E REGRAS DE NEGÓCIO
// ==========================================

test('uma pessoa tem uma conta criada automaticamente e unica por evento', function () {
    $conta1 = Conta::firstOrCreate([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento
    ], [
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    expect($conta1)->toBeInstanceOf(Conta::class);
    expect($conta1->val_saldo)->toEqual(0.00);

    // Tentar criar duplicado deve falhar devido à restrição do banco de dados (Unique composto)
    $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
    
    Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 10.00,
        'usu_inclusao' => $this->admin->id
    ]);
});

test('lancamento de transacao de deposito (aporte) aumenta o saldo da conta', function () {
    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'tip_transacao' => 'D',
        'nom_item' => 'Crédito Antecipado',
        'val_transacao' => 50.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    expect($conta->fresh()->val_saldo)->toEqual(50.00);
});

test('compra de produto reduz o estoque e diminui o saldo da conta', function () {
    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    // Lança o depósito inicial de 50.00 via transação
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'tip_transacao' => 'D',
        'nom_item' => 'Aporte Inicial',
        'val_transacao' => 50.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    $produto = Produto::create([
        'nom_produto' => 'Pão de Queijo',
        'val_preco' => 5.00,
        'qtd_produto' => 20, // Estoque
        'usu_inclusao' => $this->admin->id
    ]);

    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $produto->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => $produto->nom_produto,
        'qtd_item' => 2,
        'val_unitario' => 5.00,
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    // O saldo deve cair para R$ 40,00 e estoque para 18 unidades
    expect($conta->fresh()->val_saldo)->toEqual(40.00);
    expect($produto->fresh()->qtd_produto)->toBe(18);
});

test('estornar transacao de compra restabelece o saldo e o estoque', function () {
    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    // Lança o depósito inicial
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'tip_transacao' => 'D',
        'nom_item' => 'Aporte Inicial',
        'val_transacao' => 50.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    $produto = Produto::create([
        'nom_produto' => 'Suco de Uva',
        'val_preco' => 4.00,
        'qtd_produto' => 10,
        'usu_inclusao' => $this->admin->id
    ]);

    $transacao = Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $produto->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => $produto->nom_produto,
        'qtd_item' => 3,
        'val_unitario' => 4.00,
        'val_transacao' => 12.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    expect($conta->fresh()->val_saldo)->toEqual(38.00);
    expect($produto->fresh()->qtd_produto)->toBe(7);

    // Deletar (estornar) a transação
    $transacao->delete();

    expect($conta->fresh()->val_saldo)->toEqual(50.00);
    expect($produto->fresh()->qtd_produto)->toBe(10);
});

// ==========================================
// TESTES DO COMPONENTE VOLT (MERCADINHO)
// ==========================================

test('usuario gestor pode abrir painel de vendas e ver a pessoa do evento', function () {
    $this->actingAs($this->admin);

    Volt::test('vendas.index', ['evento' => $this->evento])
        ->assertSet('activeSubTab', 'operacao')
        ->assertSee('Operar Mercadinho')
        ->assertSee('José da Silva');
});

test('gestor pode cadastrar produtos pelo componente de catalogo', function () {
    $this->actingAs($this->admin);

    Volt::test('vendas.produtos')
        ->call('openCreateModal')
        ->set('nom_produto', 'Bolo de Cenoura')
        ->set('des_produto', 'Cobertura de chocolate')
        ->set('val_preco', '6.50')
        ->set('qtd_produto', '10')
        ->call('salvar')
        ->assertSee('Bolo de Cenoura');

    $produto = Produto::where('nom_produto', 'Bolo de Cenoura')->first();
    expect($produto)->not->toBeNull();
    expect($produto->val_preco)->toEqual(6.50);
    expect($produto->qtd_produto)->toBe(10);
});

test('gestor pode registrar compra no mercadinho usando o component Volt', function () {
    $this->actingAs($this->admin);

    $produto = Produto::create([
        'nom_produto' => 'Terço',
        'val_preco' => 15.00,
        'qtd_produto' => 5,
        'usu_inclusao' => $this->admin->id
    ]);

    Volt::test('vendas.index', ['evento' => $this->evento])
        ->call('openCompra', $this->pessoa->idt_pessoa)
        ->call('adicionarAoCarrinho', $produto->idt_produto)
        ->set('nom_avulso', 'Cafezinho')
        ->set('val_avulso_preco', '2.50')
        ->set('qtd_avulso', 2)
        ->call('finalizarCompra')
        ->assertHasNoErrors();

    $conta = Conta::where('idt_pessoa', $this->pessoa->idt_pessoa)->first();
    // Total compra: (1 * 15) + (2 * 2.50) = 20.00
    // Saldo inicial era 0.00, então deve estar -20.00
    expect($conta->val_saldo)->toEqual(-20.00);
    expect($produto->fresh()->qtd_produto)->toBe(4);
});

test('gestor pode registrar credito e quitar contas pelo componente Volt', function () {
    $this->actingAs($this->admin);

    // Registra a conta
    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    // Registra uma compra prévia de R$ 10,00 via transação
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'tip_transacao' => 'C',
        'nom_item' => 'Compra Prévia',
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    Volt::test('vendas.index', ['evento' => $this->evento])
        ->call('openCredito', $this->pessoa->idt_pessoa)
        ->set('tip_transacao_credito', 'P') // Pagamento/Quitação
        ->set('val_aporte', '10.00')
        ->set('des_aporte', 'Pagamento em PIX')
        ->call('registrarCredito')
        ->assertHasNoErrors();

    // Saldo final deve ser zerado
    expect($conta->fresh()->val_saldo)->toEqual(0.00);
});

test('qualquer pessoa pode consultar o saldo do mercadinho usando o component Volt consulta-saldo', function () {
    $produto = Produto::create([
        'nom_produto' => 'Livro de Oração',
        'val_preco' => 10.00,
        'qtd_produto' => 5,
        'usu_inclusao' => $this->admin->id
    ]);

    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $produto->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => $produto->nom_produto,
        'qtd_item' => 1,
        'val_unitario' => 10.00,
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    // O CPF formatado da factory é retornado pelo accessor, mas o CPF limpo no banco é usado na consulta
    $cpf = $this->pessoa->num_cpf_pessoa; // Cpf formatado
    $nascimento = $this->pessoa->dat_nascimento->format('Y-m-d');

    Volt::test('vendas.consulta-saldo')
        ->set('cpf', $cpf)
        ->set('dat_nascimento', $nascimento)
        ->set('idt_evento', (string) $this->evento->idt_evento)
        ->call('consultar')
        ->assertHasNoErrors()
        ->assertSee('José da Silva')
        ->assertSee('-10,00')
        ->assertSee('Livro de Oração');
});

