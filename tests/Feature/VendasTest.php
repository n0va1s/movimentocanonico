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
    
    // Remover pessoa criada automaticamente pelo boot do User para evitar duplicidade
    Pessoa::where('idt_usuario', $this->user->id)->delete();
    
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
        ->set('ind_favorito', true)
        ->call('salvar')
        ->assertSee('Bolo de Cenoura');

    $produto = Produto::where('nom_produto', 'Bolo de Cenoura')->first();
    expect($produto)->not->toBeNull();
    expect($produto->val_preco)->toEqual(6.50);
    expect($produto->qtd_produto)->toBe(10);
    expect($produto->ind_favorito)->toBeTrue();
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
        ->call('finalizarCompra')
        ->assertHasNoErrors();

    $conta = Conta::where('idt_pessoa', $this->pessoa->idt_pessoa)->first();
    // Total compra: (1 * 15) = 15.00
    // Saldo inicial era 0.00, então deve estar -15.00
    expect($conta->val_saldo)->toEqual(-15.00);
    expect($produto->fresh()->qtd_produto)->toBe(4);
});

test('compra de produto deve falhar se nao houver produto associado', function () {
    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Compras devem possuir um produto válido associado.');

    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => null,
        'tip_transacao' => 'C',
        'nom_item' => 'Item Avulso Tentativa',
        'qtd_item' => 1,
        'val_unitario' => 10.00,
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);
});

test('gestor pode filtrar compradores por equipe e cor da troca no component Volt', function () {
    $this->actingAs($this->admin);

    $outraPessoa = Pessoa::factory()->create([
        'nom_pessoa' => 'Maria Oliveira',
    ]);

    // Pessoa 1 (José da Silva) vinculada no beforeEach como participante.
    // Vamos definir a cor da troca do participante principal ($this->pessoa) como Verde ('V')
    $participante = Participante::where('idt_pessoa', $this->pessoa->idt_pessoa)->first();
    $participante->update(['tip_cor_troca' => 'V']);

    // Criar uma equipe
    $equipe = \App\Models\TipoEquipe::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'des_grupo' => 'Coordenação Geral',
    ]);

    // Vincular $outraPessoa como trabalhador desse evento na equipe
    \App\Models\Trabalhador::factory()->create([
        'idt_evento' => $this->evento->idt_evento,
        'idt_pessoa' => $outraPessoa->idt_pessoa,
        'idt_equipe' => $equipe->idt_equipe,
    ]);

    // Testar os filtros no componente
    Volt::test('vendas.index', ['evento' => $this->evento])
        // Inicialmente vê as duas pessoas
        ->assertSee('José da Silva')
        ->assertSee('Maria Oliveira')
        // Filtrar por cor Verde ('V')
        ->set('filtroCor', 'V')
        ->assertSee('José da Silva')
        ->assertDontSee('Maria Oliveira')
        // Resetar filtro de cor e filtrar por equipe
        ->set('filtroCor', '')
        ->set('filtroEquipe', $equipe->idt_equipe)
        ->assertSee('Maria Oliveira')
        ->assertDontSee('José da Silva');
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

    $produto = Produto::create([
        'nom_produto' => 'Produto Teste',
        'val_preco' => 10.00,
        'qtd_produto' => 5,
        'usu_inclusao' => $this->admin->id
    ]);

    // Registra uma compra prévia de R$ 10,00 via transação
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $produto->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => 'Compra Prévia',
        'qtd_item' => 1,
        'val_unitario' => 10.00,
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

    $this->actingAs($this->user);

    Volt::test('vendas.consulta-saldo')
        ->set('idt_evento', (string) $this->evento->idt_evento)
        ->call('consultar')
        ->assertHasNoErrors()
        ->assertSee('José da Silva')
        ->assertSee('-10,00')
        ->assertSee('Livro de Oração');
});

test('produtos sao ordenados com favoritos no topo no catalogo de compras', function () {
    $this->actingAs($this->admin);

    // Produto B comum
    $produtoComum = Produto::create([
        'nom_produto' => 'Bolo de Cenoura',
        'val_preco' => 5.00,
        'qtd_produto' => 5,
        'ind_favorito' => false,
        'usu_inclusao' => $this->admin->id
    ]);

    // Produto A favorito
    $produtoFavorito = Produto::create([
        'nom_produto' => 'Cafezinho',
        'val_preco' => 2.00,
        'qtd_produto' => 10,
        'ind_favorito' => true,
        'usu_inclusao' => $this->admin->id
    ]);

    $component = Volt::test('vendas.index', ['evento' => $this->evento]);
    
    $produtos = $component->get('produtosDisponiveis');
    
    // Cafezinho (favorito) deve ser o primeiro da lista de disponíveis
    expect($produtos->first()->nom_produto)->toEqual('Cafezinho');
});

test('gestor pode filtrar por saldo devedor e credor', function() {
    $this->actingAs($this->admin);

    // Pessoa 1: José da Silva (participante), devedor (saldo = -10.00)
    $contaJose = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => -10.00,
        'usu_inclusao' => $this->admin->id
    ]);

    // Pessoa 2: Maria Oliveira (trabalhador), credora (saldo = 20.00)
    $maria = Pessoa::factory()->create([
        'nom_pessoa' => 'Maria Oliveira',
    ]);
    \App\Models\Trabalhador::factory()->create([
        'idt_evento' => $this->evento->idt_evento,
        'idt_pessoa' => $maria->idt_pessoa,
        'idt_equipe' => \App\Models\TipoEquipe::factory()->create(['idt_movimento' => $this->movimento->idt_movimento])->idt_equipe,
    ]);
    $contaMaria = Conta::create([
        'idt_pessoa' => $maria->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 20.00,
        'usu_inclusao' => $this->admin->id
    ]);

    // Testar componente
    $component = Volt::test('vendas.index', ['evento' => $this->evento]);
    
    // Todos
    $component->assertSee('José da Silva')
              ->assertSee('Maria Oliveira');

    // Devedores
    $component->set('filtroSaldo', 'devedores')
              ->assertSee('José da Silva')
              ->assertDontSee('Maria Oliveira');

    // Credores
    $component->set('filtroSaldo', 'credores')
              ->assertSee('Maria Oliveira')
              ->assertDontSee('José da Silva');
});

test('gestor pode visualizar relatorio de vendas ordenado por quantidade', function() {
    $this->actingAs($this->admin);

    $conta = Conta::create([
        'idt_pessoa' => $this->pessoa->idt_pessoa,
        'idt_evento' => $this->evento->idt_evento,
        'val_saldo' => 0.00,
        'usu_inclusao' => $this->admin->id
    ]);

    $paoDeQueijo = Produto::create([
        'nom_produto' => 'Pão de Queijo',
        'val_preco' => 5.00,
        'qtd_produto' => 20,
        'usu_inclusao' => $this->admin->id
    ]);

    $cafezinho = Produto::create([
        'nom_produto' => 'Cafezinho',
        'val_preco' => 2.00,
        'qtd_produto' => 50,
        'usu_inclusao' => $this->admin->id
    ]);

    // Comprar 2 Pães de Queijo
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $paoDeQueijo->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => $paoDeQueijo->nom_produto,
        'qtd_item' => 2,
        'val_unitario' => 5.00,
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    // Comprar 5 Cafezinhos (Cafezinho deve ficar em primeiro no relatório)
    Transacao::create([
        'idt_conta' => $conta->idt_conta,
        'idt_produto' => $cafezinho->idt_produto,
        'tip_transacao' => 'C',
        'nom_item' => $cafezinho->nom_produto,
        'qtd_item' => 5,
        'val_unitario' => 2.00,
        'val_transacao' => 10.00,
        'dat_transacao' => now(),
        'usu_inclusao' => $this->admin->id
    ]);

    $component = Volt::test('vendas.index', ['evento' => $this->evento]);
    
    $relatorio = $component->get('relatorioVendas');

    expect($relatorio)->toHaveCount(2);
    expect($relatorio[0]->nom_item)->toEqual('Cafezinho');
    expect($relatorio[0]->total_qtd)->toEqual(5);
    expect($relatorio[0]->total_valor)->toEqual(10.00);

    expect($relatorio[1]->nom_item)->toEqual('Pão de Queijo');
    expect($relatorio[1]->total_qtd)->toEqual(2);
    expect($relatorio[1]->total_valor)->toEqual(10.00);

    // E ver se a aba Relatório de Vendas renderiza os dados
    $component->set('activeSubTab', 'relatorio')
              ->assertSee('Relatório de Vendas')
              ->assertSee('Cafezinho')
              ->assertSee('Pão de Queijo');
});


