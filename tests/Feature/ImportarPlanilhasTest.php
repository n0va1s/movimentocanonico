<?php

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use App\Models\Trabalhador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    createMovimentos();
    $this->movimento = TipoMovimento::first();

    // Cria usuários com perfis variados
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->espec = User::factory()->create([
        'role' => 'espec',
        'idt_movimento' => $this->movimento->idt_movimento,
    ]);
    $this->coord = User::factory()->create(['role' => 'coord']); // Não deve ter acesso
    $this->user = User::factory()->create(['role' => 'user']); // Não deve ter acesso

    // Cria um evento ativo de teste (data de início no futuro)
    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'dat_inicio' => now()->addDays(10)->format('Y-m-d'),
        'dat_termino' => now()->addDays(12)->format('Y-m-d'),
    ]);

    // Limpa os arquivos de log de teste se existirem
    @unlink(storage_path('logs/import_participantes.log'));
    @unlink(storage_path('logs/import_trabalhadores.log'));
});

// ==========================================
// TESTES DE AUTORIZAÇÃO E ROTAS
// ==========================================

test('usuario comum nao pode acessar interface de importacao', function () {
    $this->actingAs($this->user)
        ->get(route('eventos.importar'))
        ->assertStatus(403);
});

test('coordenador nao pode acessar interface de importacao', function () {
    $this->actingAs($this->coord)
        ->get(route('eventos.importar'))
        ->assertStatus(403);
});

test('especialista pode acessar interface de importacao', function () {
    $this->actingAs($this->espec)
        ->get(route('eventos.importar'))
        ->assertStatus(200)
        ->assertSee('Importar Planilhas de Eventos')
        ->assertSee($this->evento->des_evento);
});

test('admin pode acessar interface de importacao', function () {
    $this->actingAs($this->admin)
        ->get(route('eventos.importar'))
        ->assertStatus(200)
        ->assertSee('Importar Planilhas de Eventos');
});

// ==========================================
// TESTES DE IMPORTAÇÃO DE PARTICIPANTES
// ==========================================

test('admin pode importar participantes de planilha CSV e cadastrar novas pessoas e usuarios', function () {
    $this->actingAs($this->admin);

    $csvContent = "CPF;Nome;Apelido;Telefone;Email;Data Nascimento;Genero;Tamanho Camiseta;Endereco;Cor Troca;Taxa Pagou;Presente\n".
                  "12345678901;Maria Importada;Mari;61999999999;maria.importada@gmail.com;15/05/1995;F;M;Endereço Maria;azul;Sim;Sim\n".
                  '98765432109;Pedro Importado;Pepe;61988888888;pedro.importado@gmail.com;01/12/1990;M;GG;Endereço Pedro;;Não;Não';

    $file = UploadedFile::fake()->createWithContent('participantes.csv', $csvContent);

    $response = $this->post(route('eventos.importar.participantes'), [
        'evento_id' => $this->evento->idt_evento,
        'arquivo_participantes' => $file,
    ]);

    $response->assertRedirect(route('eventos.importar'));
    $response->assertSessionHas('success');

    // Verifica se Maria Importada foi cadastrada corretamente
    $maria = Pessoa::where('eml_pessoa', 'maria.importada@gmail.com')->first();
    expect($maria)->not->toBeNull()
        ->and($maria->nom_pessoa)->toBe('Maria Importada')
        ->and($maria->num_cpf_pessoa)->toBe('123.456.789-01')
        ->and($maria->dat_nascimento->format('Y-m-d'))->toBe('1995-05-15')
        ->and($maria->tip_genero->value)->toBe('F')
        ->and($maria->tam_camiseta->value)->toBe('M');

    // Verifica se Usuário para Maria foi criado e devidamente associado
    $userMaria = User::where('email', 'maria.importada@gmail.com')->first();
    expect($userMaria)->not->toBeNull()
        ->and($maria->idt_usuario)->toBe($userMaria->id);

    // Verifica se Pedro Importado foi cadastrado corretamente
    $pedro = Pessoa::where('eml_pessoa', 'pedro.importado@gmail.com')->first();
    expect($pedro)->not->toBeNull()
        ->and($pedro->nom_pessoa)->toBe('Pedro Importado')
        ->and($pedro->num_cpf_pessoa)->toBe('987.654.321-09')
        ->and($pedro->dat_nascimento->format('Y-m-d'))->toBe('1990-12-01')
        ->and($pedro->tip_genero->value)->toBe('M');

    // Verifica se os participantes foram vinculados ao evento
    $partMaria = Participante::where('idt_pessoa', $maria->idt_pessoa)->where('idt_evento', $this->evento->idt_evento)->first();
    expect($partMaria)->not->toBeNull()
        ->and($partMaria->tip_cor_troca)->toBe('azul')
        ->and($partMaria->ind_taxa_pagou)->toBeTrue()
        ->and($partMaria->ind_presente)->toBeTrue();

    $partPedro = Participante::where('idt_pessoa', $pedro->idt_pessoa)->where('idt_evento', $this->evento->idt_evento)->first();
    expect($partPedro)->not->toBeNull()
        ->and($partPedro->tip_cor_troca)->toBeNull()
        ->and($partPedro->ind_taxa_pagou)->toBeFalse()
        ->and($partPedro->ind_presente)->toBeFalse();

    // Verifica se o log foi gravado corretamente
    expect(file_exists(storage_path('logs/import_participantes.log')))->toBeTrue();
    $logContent = file_get_contents(storage_path('logs/import_participantes.log'));
    expect($logContent)->toContain('=== INÍCIO DA IMPORTAÇÃO DE PARTICIPANTES')
        ->toContain('Nova Pessoa cadastrada')
        ->toContain('=== FIM DA IMPORTAÇÃO DE PARTICIPANTES');
});

test('importador atualiza pessoa existente e garante vinculo de usuario', function () {
    $this->actingAs($this->admin);

    // Cria pessoa existente no banco de dados, mas SEM usuário associado (usando saveQuietly para ignorar boot event)
    $pessoaExistente = new Pessoa([
        'nom_pessoa' => 'Antiga Maria',
        'eml_pessoa' => 'existente.maria@gmail.com',
        'dat_nascimento' => '1995-05-15',
        'num_cpf_pessoa' => '12345678901',
    ]);
    $pessoaExistente->saveQuietly();

    expect($pessoaExistente->idt_usuario)->toBeNull();

    $csvContent = "CPF;Nome;Apelido;Telefone;Email;Data Nascimento;Genero;Tamanho Camiseta;Endereco;Cor Troca;Taxa Pagou;Presente\n".
                  '12345678901;Maria Atualizada;Mari;61999999999;existente.maria@gmail.com;15/05/1995;F;M;Novo Endereço;azul;Sim;Sim';

    $file = UploadedFile::fake()->createWithContent('participantes.csv', $csvContent);

    $this->post(route('eventos.importar.participantes'), [
        'evento_id' => $this->evento->idt_evento,
        'arquivo_participantes' => $file,
    ]);

    // Verifica que a pessoa existente foi atualizada ao invés de criar outra
    expect(Pessoa::where('num_cpf_pessoa', '12345678901')->count())->toBe(1);

    $maria = $pessoaExistente->fresh();
    expect($maria->nom_pessoa)->toBe('Maria Atualizada')
        ->and($maria->des_endereco)->toBe('Novo Endereço')
        ->and($maria->tam_camiseta->value)->toBe('M');

    // Verifica que um Usuário foi gerado e associado
    $user = User::where('email', 'existente.maria@gmail.com')->first();
    expect($user)->not->toBeNull()
        ->and($maria->idt_usuario)->toBe($user->id);
});

// ==========================================
// TESTES DE IMPORTAÇÃO DE TRABALHADORES
// ==========================================

test('admin pode importar trabalhadores de planilha CSV, associando-os a equipes', function () {
    $this->actingAs($this->admin);

    // Garante que a equipe "Bandinha" do movimento está cadastrada
    $equipe = TipoEquipe::where('des_grupo', 'Bandinha')->first();
    if (! $equipe) {
        $equipe = TipoEquipe::create([
            'des_grupo' => 'Bandinha',
            'idt_movimento' => $this->movimento->idt_movimento,
        ]);
    }

    $csvContent = "CPF;Nome;Apelido;Telefone;Email;Data Nascimento;Genero;Tamanho Camiseta;Endereco;Equipe;Coordenador;Primeira Vez;Recomendado;Lideranca;Destaque;Avaliacao;Camiseta Pediu;Camiseta Pagou;Taxa Pagou;Presente\n".
                  '11111111111;José Trabalhador;Zezinho;61977777777;jose.trabalhador@gmail.com;1985-02-10;M;G;Endereço José;Bandinha;Sim;Não;Sim;Sim;Não;Sim;Sim;Sim;Sim;Sim';

    $file = UploadedFile::fake()->createWithContent('trabalhadores.csv', $csvContent);

    $response = $this->post(route('eventos.importar.trabalhadores'), [
        'evento_id' => $this->evento->idt_evento,
        'arquivo_trabalhadores' => $file,
    ]);

    $response->assertRedirect(route('eventos.importar'));
    $response->assertSessionHas('success');

    // Verifica se José Trabalhador foi cadastrado corretamente
    $jose = Pessoa::where('eml_pessoa', 'jose.trabalhador@gmail.com')->first();
    expect($jose)->not->toBeNull()
        ->and($jose->nom_pessoa)->toBe('José Trabalhador')
        ->and($jose->num_cpf_pessoa)->toBe('111.111.111-11');

    // Verifica se Trabalhador foi vinculado à equipe Bandinha
    $trabJose = Trabalhador::where('idt_pessoa', $jose->idt_pessoa)->where('idt_evento', $this->evento->idt_evento)->first();
    expect($trabJose)->not->toBeNull()
        ->and($trabJose->idt_equipe)->toBe($equipe->idt_equipe)
        ->and($trabJose->ind_coordenador)->toBeTrue()
        ->and($trabJose->ind_primeira_vez)->toBeFalse()
        ->and($trabJose->ind_recomendado)->toBeTrue()
        ->and($trabJose->ind_lideranca)->toBeTrue()
        ->and($trabJose->ind_destaque)->toBeFalse()
        ->and($trabJose->ind_presente)->toBeTrue();

    // Verifica se o log correspondente foi gerado
    expect(file_exists(storage_path('logs/import_trabalhadores.log')))->toBeTrue();
});
