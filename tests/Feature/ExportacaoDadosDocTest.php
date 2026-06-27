<?php

use App\Models\Pessoa;
use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Participante;
use App\Models\TipoMovimento;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    createMovimentos(); // Configura os tipos de movimentos e equipes padrão
});

/**
 * ============================================================================
 * GUIA DE DOCUMENTAÇÃO E USO DA API DE EXPORTAÇÃO DE DADOS
 * ============================================================================
 * 
 * Esta API fornece informações completas (sem mascaramento) sobre pessoas
 * vinculadas a eventos, seja como "trabalhadores" ou como "participantes".
 * Ela é voltada para integração com sistemas externos para carga de dados.
 */

test('1. Como funciona a listagem e estrutura de dados de pessoas por evento', function () {
    /**
     * ENDPOINT: GET /api/eventos/{id_evento}/pessoas
     * 
     * DESCRIÇÃO:
     * Retorna todas as pessoas vinculadas ao evento especificado que atuam
     * como trabalhadores ou participantes.
     * 
     * FORMATO DO RETORNO (JSON):
     * {
     *   "sucesso": true,           // Indica se a requisição foi bem sucedida
     *   "total": 2,                // Quantidade total de pessoas retornadas
     *   "dados": [                 // Lista contendo os dados das pessoas
     *     {
     *       "id_pessoa": 1,
     *       "nome": "Gabriel",
     *       "apelido": "Gabi",
     *       "cpf": "000.000.000-00", // CPF completo sem mascaras de privacidade
     *       "telefone": "(61) 99999-9999",
     *       "email": "gabriel@example.com",
     *       "data_nascimento": "2002-12-12",
     *       "sexo": "M",
     *       "endereco": "...",
     *       "contexto_evento": {   // Contexto específico da pessoa no evento
     *         "id_evento": 41,
     *         "perfil": "trabalhador", // 'trabalhador' ou 'participante'
     *         "equipe": "Alimentação", // Nome da equipe (se trabalhador, senão null)
     *         "cor_troca": null       // Cor de troca (se participante, senão null)
     *       }
     *     }
     *   ]
     * }
     */

    // Cenário de teste para validar a explicação acima:
    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $equipe = TipoEquipe::firstOrCreate(
        ['des_grupo' => 'Acolhimento'],
        ['idt_movimento' => $movimento->idt_movimento]
    );

    // Pessoa 1: Trabalhador
    $trabalhador = Pessoa::factory()->create(['idt_parceiro' => null]);
    Trabalhador::factory()->create([
        'idt_pessoa' => $trabalhador->idt_pessoa,
        'idt_evento' => $evento->idt_evento,
        'idt_equipe' => $equipe->idt_equipe,
    ]);

    // Pessoa 2: Participante
    $participante = Pessoa::factory()->create(['idt_parceiro' => null]);
    Participante::factory()->create([
        'idt_pessoa' => $participante->idt_pessoa,
        'idt_evento' => $evento->idt_evento,
        'tip_cor_troca' => 'Azul',
    ]);

    // Execução da requisição
    $response = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas");

    // Validação da estrutura explicada
    $response->assertStatus(200)
        ->assertJsonStructure([
            'sucesso',
            'total',
            'dados' => [
                '*' => [
                    'id_pessoa',
                    'nome',
                    'apelido',
                    'cpf',
                    'telefone',
                    'email',
                    'data_nascimento',
                    'sexo',
                    'endereco',
                    'contexto_evento' => [
                        'id_evento',
                        'perfil',
                        'equipe',
                        'cor_troca',
                    ]
                ]
            ]
        ])
        ->assertJson([
            'sucesso' => true,
            'total' => 2,
            'dados' => [
                [
                    'id_pessoa' => $trabalhador->idt_pessoa,
                    'contexto_evento' => [
                        'perfil' => 'trabalhador',
                        'equipe' => 'Acolhimento',
                        'cor_troca' => null
                    ]
                ],
                [
                    'id_pessoa' => $participante->idt_pessoa,
                    'contexto_evento' => [
                        'perfil' => 'participante',
                        'equipe' => null,
                        'cor_troca' => 'Azul'
                    ]
                ]
            ]
        ]);
});

test('2. Como funciona a busca individual de uma pessoa vinculada ao evento', function () {
    /**
     * ENDPOINT: GET /api/eventos/{id_evento}/pessoas/{id_pessoa}
     * 
     * DESCRIÇÃO:
     * Retorna os dados completos e o contexto de uma única pessoa específica,
     * desde que ela possua vínculo no evento informado.
     * 
     * ERROS POSSÍVEIS:
     * - Se a pessoa não existir no banco de dados OR não estiver vinculada ao evento
     *   como trabalhador ou participante, o sistema retornará Status 404
     *   com uma mensagem explicativa.
     */

    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);
    $pessoa = Pessoa::factory()->create(['idt_parceiro' => null]);

    // Caso 1: Sem vínculo (Retorna 404)
    $response404 = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/{$pessoa->idt_pessoa}");
    $response404->assertStatus(404)
        ->assertJson([
            'sucesso' => false,
            'mensagem' => 'Pessoa não encontrada ou sem vínculo com o evento informado.'
        ]);

    // Caso 2: Com vínculo ativo (Retorna 200 com os dados planos da pessoa)
    Participante::factory()->create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento->idt_evento,
        'tip_cor_troca' => 'Vermelho'
    ]);

    $response200 = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas/{$pessoa->idt_pessoa}");
    $response200->assertStatus(200)
        ->assertJson([
            'id_pessoa' => $pessoa->idt_pessoa,
            'nome' => $pessoa->nom_pessoa,
            'contexto_evento' => [
                'id_evento' => $evento->idt_evento,
                'perfil' => 'participante',
                'cor_troca' => 'Vermelho'
            ]
        ]);
});

test('3. Como funcionam os parâmetros de carga incremental data_inicio e data_fim', function () {
    /**
     * PARÂMETROS:
     * - data_inicio: Filtra registros modificados a partir desta data (campo updated_at >= data_inicio).
     * - data_fim: Filtra registros modificados até esta data (campo updated_at <= data_fim).
     * 
     * USO:
     * Esses parâmetros são passados via Query String na rota de listagem para permitir
     * que sistemas externos busquem apenas as modificações mais recentes (carga incremental).
     * 
     * EXEMPLO DE URL:
     * GET /api/eventos/{id_evento}/pessoas?data_inicio=2026-06-20&data_fim=2026-06-25
     */

    $movimento = TipoMovimento::first();
    $evento = Evento::factory()->create(['idt_movimento' => $movimento->idt_movimento]);

    $pessoaA = Pessoa::factory()->create(['idt_parceiro' => null]);
    $pessoaB = Pessoa::factory()->create(['idt_parceiro' => null]);

    Participante::factory()->create(['idt_pessoa' => $pessoaA->idt_pessoa, 'idt_evento' => $evento->idt_evento]);
    Participante::factory()->create(['idt_pessoa' => $pessoaB->idt_pessoa, 'idt_evento' => $evento->idt_evento]);

    // Simulando datas de atualizações diferentes no banco de dados
    DB::table('pessoa')->where('idt_pessoa', $pessoaA->idt_pessoa)->update(['updated_at' => '2026-06-15 10:00:00']);
    DB::table('pessoa')->where('idt_pessoa', $pessoaB->idt_pessoa)->update(['updated_at' => '2026-06-25 15:00:00']);

    // Caso A: Usando apenas data_inicio (filtra a partir de 2026-06-20) -> Deve retornar apenas a Pessoa B
    $responseInicio = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas?data_inicio=2026-06-20");
    $responseInicio->assertStatus(200)
        ->assertJson([
            'sucesso' => true,
            'total' => 1,
            'dados' => [
                ['id_pessoa' => $pessoaB->idt_pessoa]
            ]
        ]);

    // Caso B: Usando apenas data_fim (filtra até 2026-06-20) -> Deve retornar apenas a Pessoa A
    $responseFim = $this->getJson("/api/eventos/{$evento->idt_evento}/pessoas?data_fim=2026-06-20");
    $responseFim->assertStatus(200)
        ->assertJson([
            'sucesso' => true,
            'total' => 1,
            'dados' => [
                ['id_pessoa' => $pessoaA->idt_pessoa]
            ]
        ]);
});
