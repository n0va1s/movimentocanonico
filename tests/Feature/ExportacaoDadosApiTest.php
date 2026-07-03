<?php

use App\Models\Pessoa;
use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Participante;
use App\Models\TipoMovimento;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    createMovimentos(); // Cria os tipos de movimento base e equipes padrão
    $this->user = createUser();
});

test('retorna 401 para qualquer rota da API sem token de autenticação', function () {
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas");
    $response->assertStatus(401);

    $responseShow = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/1");
    $responseShow->assertStatus(401);
});

test('retorna 404 com mensagem amigável quando pessoa não existe no show', function () {
    Sanctum::actingAs($this->user);
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/999999");

    $response->assertStatus(404)
        ->assertJson([
            'sucesso' => false,
            'mensagem' => 'Pessoa não encontrada ou sem vínculo com o evento informado.'
        ]);
});

test('retorna 404 com mensagem amigável quando a pessoa existe mas não tem vínculo com o evento', function () {
    Sanctum::actingAs($this->user);
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $pessoa = Pessoa::factory()->create(['idt_parceiro' => null]);

    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/{$pessoa->idt_pessoa}");

    $response->assertStatus(404)
        ->assertJson([
            'sucesso' => false,
            'mensagem' => 'Pessoa não encontrada ou sem vínculo com o evento informado.'
        ]);
});

test('lista apenas pessoas vinculadas ao evento (trabalhador ou participante)', function () {
    Sanctum::actingAs($this->user);
    $movimento = TipoMovimento::first();
    $evento1 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $evento2 = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $equipe = TipoEquipe::firstOrCreate(
        ['des_grupo' => 'Alimentação'],
        ['idt_movimento' => $movimento->idt_movimento]
    );

    // Pessoa 1: Trabalhador no Evento 1
    $pessoa1 = Pessoa::factory()->create(['idt_parceiro' => null]);
    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoa1->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
        'idt_equipe' => $equipe->idt_equipe
    ]);

    // Pessoa 2: Participante no Evento 1
    $pessoa2 = Pessoa::factory()->create(['idt_parceiro' => null]);
    Participante::factory()->create([
        'idt_pessoa' => $pessoa2->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
        'tip_cor_troca' => 'Verde'
    ]);

    // Pessoa 3: Participante no Evento 2 (outro evento)
    $pessoa3 = Pessoa::factory()->create(['idt_parceiro' => null]);
    Participante::factory()->create([
        'idt_pessoa' => $pessoa3->idt_pessoa,
        'idt_evento' => $evento2->idt_evento,
        'tip_cor_troca' => 'Azul'
    ]);

    $response = $this->getJson("/api/eventos/{$evento1->idt_evento}/pessoas");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'dados')
        ->assertJson(fn (AssertableJson $json) =>
            $json->where('sucesso', true)
                ->where('total', 2)
                ->has('dados.0', fn (AssertableJson $sub) =>
                    $sub->where('id_pessoa', $pessoa1->idt_pessoa)
                        ->where('nome', $pessoa1->nom_pessoa)
                        ->where('cpf', $pessoa1->num_cpf_pessoa)
                        ->where('telefone', $pessoa1->tel_pessoa)
                        ->where('data_nascimento', $pessoa1->getDataNascimentoFormatada())
                        ->where('sexo', $pessoa1->tip_genero?->value ?? $pessoa1->tip_genero)
                        ->has('contexto_evento', fn (AssertableJson $ctx) =>
                            $ctx->where('id_evento', $evento1->idt_evento)
                                ->where('perfil', 'trabalhador')
                                ->where('equipe', 'Alimentação')
                                ->where('cor_troca', null)
                        )
                        ->etc()
                )
                ->has('dados.1', fn (AssertableJson $sub) =>
                    $sub->where('id_pessoa', $pessoa2->idt_pessoa)
                        ->where('nome', $pessoa2->nom_pessoa)
                        ->where('cpf', $pessoa2->num_cpf_pessoa)
                        ->where('telefone', $pessoa2->tel_pessoa)
                        ->where('data_nascimento', $pessoa2->getDataNascimentoFormatada())
                        ->where('sexo', $pessoa2->tip_genero?->value ?? $pessoa2->tip_genero)
                        ->has('contexto_evento', fn (AssertableJson $ctx) =>
                            $ctx->where('id_evento', $evento1->idt_evento)
                                ->where('perfil', 'participante')
                                ->where('equipe', null)
                                ->where('cor_troca', 'Verde')
                        )
                        ->etc()
                )
        );
});

test('retorna detalhes de uma única pessoa vinculada ao evento no show', function () {
    Sanctum::actingAs($this->user);
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $equipe = TipoEquipe::firstOrCreate(
        ['des_grupo' => 'Alimentação'],
        ['idt_movimento' => $movimento->idt_movimento]
    );

    $pessoa = Pessoa::factory()->create(['idt_parceiro' => null]);
    Trabalhador::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento->idt_evento,
        'idt_equipe' => $equipe->idt_equipe
    ]);

    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/{$pessoa->idt_pessoa}");

    $response->assertStatus(200)
        ->assertJson([
            'id_pessoa' => $pessoa->idt_pessoa,
            'nome' => $pessoa->nom_pessoa,
            'apelido' => $pessoa->nom_apelido,
            'cpf' => $pessoa->num_cpf_pessoa,
            'telefone' => $pessoa->tel_pessoa,
            'email' => $pessoa->eml_pessoa,
            'data_nascimento' => $pessoa->getDataNascimentoFormatada(),
            'sexo' => $pessoa->tip_genero?->value ?? $pessoa->tip_genero,
            'endereco' => $pessoa->des_endereco,
            'contexto_evento' => [
                'id_evento' => $evento->idt_evento,
                'perfil' => 'trabalhador',
                'equipe' => 'Alimentação',
                'cor_troca' => null
            ]
        ]);
});

test('aplica filtros incrementais de data_inicio e data_fim corretos na listagem', function () {
    Sanctum::actingAs($this->user);
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $pessoaA = Pessoa::factory()->create(['idt_parceiro' => null]);
    $pessoaB = Pessoa::factory()->create(['idt_parceiro' => null]);
    $pessoaC = Pessoa::factory()->create(['idt_parceiro' => null]);

    Participante::factory()->create(['idt_pessoa' => $pessoaA->idt_pessoa, 'idt_evento' => $evento->idt_evento]);
    Participante::factory()->create(['idt_pessoa' => $pessoaB->idt_pessoa, 'idt_evento' => $evento->idt_evento]);
    Participante::factory()->create(['idt_pessoa' => $pessoaC->idt_pessoa, 'idt_evento' => $evento->idt_evento]);

    // Atualiza datas diretamente no banco para testar de forma determinística
    DB::table('pessoa')->where('idt_pessoa', $pessoaA->idt_pessoa)->update(['updated_at' => '2026-06-25 12:00:00']);
    DB::table('pessoa')->where('idt_pessoa', $pessoaB->idt_pessoa)->update(['updated_at' => '2026-06-27 12:00:00']);
    DB::table('pessoa')->where('idt_pessoa', $pessoaC->idt_pessoa)->update(['updated_at' => '2026-06-29 12:00:00']);

    // Cenário 1: Apenas data_inicio
    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas?data_inicio=2026-06-27 00:00:00");
    $response->assertStatus(200)
        ->assertJsonCount(2, 'dados')
        ->assertJsonPath('dados.0.id_pessoa', $pessoaB->idt_pessoa)
        ->assertJsonPath('dados.1.id_pessoa', $pessoaC->idt_pessoa);

    // Cenário 2: Apenas data_fim
    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas?data_fim=2026-06-27 23:59:59");
    $response->assertStatus(200)
        ->assertJsonCount(2, 'dados')
        ->assertJsonPath('dados.0.id_pessoa', $pessoaA->idt_pessoa)
        ->assertJsonPath('dados.1.id_pessoa', $pessoaB->idt_pessoa);

    // Cenário 3: Ambas as datas combinadas
    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas?data_inicio=2026-06-26 00:00:00&data_fim=2026-06-28 00:00:00");
    $response->assertStatus(200)
        ->assertJsonCount(1, 'dados')
        ->assertJsonPath('dados.0.id_pessoa', $pessoaB->idt_pessoa);
});
