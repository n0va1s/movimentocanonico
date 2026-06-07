<?php

use App\Enums\TipoSituacao;
use App\Models\Ficha;
use App\Models\FichaVem;
use App\Models\TipoMovimento;
use App\Models\TipoResponsavel;
use App\Models\TipoRestricao;
use App\Models\User;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Dados base reutilizaveis ──────────────────────────────────────────────────

function dadosCandidatoVem(array $overrides = []): array
{
    return array_merge([
        'tip_genero' => 'M',
        'nom_candidato' => 'Lucas Oliveira',
        'nom_apelido' => 'Luca',
        'dat_nascimento' => '2007-03-15',
        'tel_candidato' => '61999991111',
        'eml_candidato' => 'lucas@email.com',
        'des_endereco' => 'Rua das Palmeiras, 42',
        'tam_camiseta' => 'M',
        'tip_como_soube' => 'IND',
        'ind_catolico' => 1,
        'ind_toca_instrumento' => 0,
        'ind_consentimento' => 1,
        'ind_restricao' => 0,
        'txt_observacao' => null,
    ], $overrides);
}

function dadosVem(array $overrides = []): array
{
    return array_merge([
        'des_onde_estuda' => 'Escola Estadual Centro',
        'des_mora_quem' => 'Pais',
        'nom_mae' => 'Ana Oliveira',
        'tel_mae' => '61988882222',
        'eml_mae' => 'ana@email.com',
        'nom_pai' => null,
        'tel_pai' => null,
        'eml_pai' => null,
        'nom_responsavel' => null,
        'tel_responsavel' => null,
        'eml_responsavel' => null,
        'ind_batizado' => 1,
        'ind_primeira_comunhao' => 1,
        'ind_crismado' => 0,
        'nom_paroquia' => 'Paroquia Sao Jose',
    ], $overrides);
}

// ── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->user);

    \DB::table('tipo_movimento')->insertOrIgnore([
        ['idt_movimento' => 1, 'nom_movimento' => 'Encontro de Casais com Cristo', 'des_sigla' => 'ECC', 'dat_inicio' => '1980-01-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 2, 'nom_movimento' => 'Encontro de Adolescentes com Cristo', 'des_sigla' => 'VEM', 'dat_inicio' => '2000-07-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 3, 'nom_movimento' => 'Encontro de Jovens com Cristo', 'des_sigla' => 'Segue-Me', 'dat_inicio' => '1990-12-31', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->evento = Evento::factory()->create(['idt_movimento' => TipoMovimento::VEM]);

    $this->responsavel = TipoResponsavel::factory()->create();
    $this->restricoes = TipoRestricao::factory()->count(3)->create();
});

// ── LISTAGEM E FORMULARIOS ────────────────────────────────────────────────────

describe('FichaVemController - LISTAGEM E FORMULARIOS', function () {

    test('pode acessar listagem de fichas VEM', function () {
        $this->get(route('vem.index'))
            ->assertStatus(200)
            ->assertViewIs('ficha.listVEM')
            ->assertViewHas('fichas');
    });

    test('listagem retorna apenas fichas do movimento VEM', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $response = $this->get(route('vem.index'));

        $response->assertStatus(200);
        $fichas = $response->viewData('fichas');
        expect($fichas->total())->toBeGreaterThanOrEqual(1);
    });

    test('listagem filtra por nome do candidato', function () {
        Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Candidato Unico VEM',
        ]);

        $this->get(route('vem.index', ['search' => 'Candidato Unico VEM']))
            ->assertStatus(200)
            ->assertViewHas('fichas');
    });

    test('listagem filtra por evento', function () {
        $evento2 = Evento::factory()->create(['idt_movimento' => TipoMovimento::VEM]);
        
        $ficha1 = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        $ficha2 = Ficha::factory()->create(['idt_evento' => $evento2->idt_evento]);

        $response = $this->get(route('vem.index', ['evento' => $this->evento->idt_evento]));
        $response->assertStatus(200);
        $fichas = $response->viewData('fichas');
        
        expect($fichas->pluck('idt_ficha'))->toContain($ficha1->idt_ficha);
        expect($fichas->pluck('idt_ficha'))->not->toContain($ficha2->idt_ficha);
    });

    test('listagem filtra por situacao', function () {
        $fichaNova = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::NOVA,
        ]);
        $fichaAprovada = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::APROVADA,
        ]);

        $response = $this->get(route('vem.index', ['situacao' => 'A']));
        $response->assertStatus(200);
        $fichas = $response->viewData('fichas');

        expect($fichas->pluck('idt_ficha'))->toContain($fichaAprovada->idt_ficha);
        expect($fichas->pluck('idt_ficha'))->not->toContain($fichaNova->idt_ficha);
    });

    test('pode acessar formulario de criacao', function () {
        $this->get(route('vem.create'))
            ->assertStatus(200)
            ->assertViewIs('ficha.formVEM');
    });

    test('formulario de criacao contem dados necessarios na view', function () {
        $response = $this->get(route('vem.create'));

        $response->assertStatus(200);
        $response->assertViewHas('ficha');
        $response->assertViewHas('eventos');
    });
});

// ── INCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaVemController - INCLUSAO', function () {

    test('pode criar ficha VEM com dados obrigatorios e mae preenchida', function () {
        $payload = array_merge(
            dadosCandidatoVem(),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'lucas@email.com')->first();

        expect($ficha)->not->toBeNull();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'des_onde_estuda' => 'Escola Estadual Centro',
            'des_mora_quem' => 'Pais',
        ]);
    });

    test('pode criar ficha VEM com apenas pai preenchido', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'pai_only@email.com']),
            dadosVem([
                'nom_mae' => null,
                'tel_mae' => null,
                'eml_mae' => null,
                'nom_pai' => 'Jose Oliveira',
                'tel_pai' => '61977773333',
                'eml_pai' => 'jose@email.com',
            ]),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'pai_only@email.com')->first();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_pai' => 'Jose Oliveira',
        ]);
    });

    test('pode criar ficha VEM com apenas responsavel preenchido', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'resp_only@email.com']),
            dadosVem([
                'nom_mae' => null,
                'tel_mae' => null,
                'eml_mae' => null,
                'nom_responsavel' => 'Tia Carla',
                'tel_responsavel' => '61966664444',
                'eml_responsavel' => 'carla@email.com',
            ]),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'resp_only@email.com')->first();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_responsavel' => 'Tia Carla',
        ]);
    });

    test('pode criar ficha VEM com restricoes de saude', function () {
        $payload = array_merge(
            dadosCandidatoVem([
                'eml_candidato' => 'restricao@email.com',
                'ind_restricao' => 1,
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
                'restricoes' => [
                    $this->restricoes[0]->idt_restricao => true,
                    $this->restricoes[1]->idt_restricao => true,
                ],
                'complementos' => [
                    $this->restricoes[0]->idt_restricao => 'Alergia a amendoim',
                    $this->restricoes[1]->idt_restricao => 'Asma',
                ],
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'restricao@email.com')->first();

        expect($ficha->fichaSaude)->toHaveCount(2);
    });

    test('pode criar ficha VEM com candidato crismado e paroquia', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'crismado@email.com']),
            dadosVem([
                'ind_batizado' => 1,
                'ind_primeira_comunhao' => 1,
                'ind_crismado' => 1,
                'nom_paroquia' => 'Paroquia Nossa Senhora',
            ]),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'crismado@email.com')->first();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'ind_crismado' => true,
            'nom_paroquia' => 'Paroquia Nossa Senhora',
        ]);
    });

    test('nao cria ficha_vem quando nenhum responsavel e informado', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'sem_resp@email.com']),
            dadosVem([
                'nom_mae' => null,
                'tel_mae' => null,
                'eml_mae' => null,
                'nom_pai' => null,
                'tel_pai' => null,
                'eml_pai' => null,
                'nom_responsavel' => null,
                'tel_responsavel' => null,
                'eml_responsavel' => null,
            ]),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors(['responsaveis']);
    });

    test('falha ao criar ficha sem campos obrigatorios do candidato', function () {
        $this->post(route('vem.store'), [
            'idt_evento' => $this->evento->idt_evento,
        ])
            ->assertSessionHasErrors([
                'nom_candidato',
                'dat_nascimento',
                'eml_candidato',
                'tam_camiseta',
                'ind_consentimento',
            ]);
    });

    test('falha ao criar ficha sem campos obrigatorios do VEM', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'sem_vem@email.com']),
            [
                'idt_evento' => $this->evento->idt_evento,
                'nom_mae' => 'Mae Teste',
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors([
                'idt_falar_com',
                'des_onde_estuda',
                'des_mora_quem',
                'ind_batizado',
                'ind_primeira_comunhao',
                'ind_crismado',
            ]);
    });

    test('falha ao criar ficha com email invalido do candidato', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'email-invalido']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors(['eml_candidato']);
    });

    test('falha ao criar ficha com email invalido da mae', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'candidato@email.com']),
            dadosVem(['eml_mae' => 'email-invalido']),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors(['eml_mae']);
    });

    test('falha ao criar ficha sem consentimento', function () {
        $payload = array_merge(
            dadosCandidatoVem(['ind_consentimento' => 0]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors(['ind_consentimento']);
    });

    test('falha ao criar ficha com idt_falar_com inexistente', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'falar_invalido@email.com']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => 99999,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertSessionHasErrors(['idt_falar_com']);
    });
});

// ── VISUALIZACAO E EDICAO ─────────────────────────────────────────────────────

describe('FichaVemController - VISUALIZACAO E EDICAO', function () {

    test('pode visualizar ficha VEM existente', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.show', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formVEM')
            ->assertViewHas('ficha');
    });

    test('pode acessar formulario de edicao', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.edit', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formVEM')
            ->assertViewHas('ficha');
    });
});

// ── ALTERACAO ─────────────────────────────────────────────────────────────────

describe('FichaVemController - ALTERACAO', function () {

    test('pode atualizar dados do candidato', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Nome Original',
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem([
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'atualizado@email.com',
                'tip_genero' => 'F',
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->refresh();

        $this->assertEquals('Nome Atualizado', $ficha->nom_candidato);
        $this->assertEquals('atualizado@email.com', $ficha->eml_candidato);
    });

    test('pode atualizar dados do VEM (escola e responsavel)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'des_onde_estuda' => 'Escola Antiga',
        ]);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => $ficha->eml_candidato]),
            dadosVem(['des_onde_estuda' => 'Escola Nova']),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->fichaVem->refresh();

        $this->assertEquals('Escola Nova', $ficha->fichaVem->des_onde_estuda);
    });

    test('cria ficha_vem ao atualizar ficha que nao tinha', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);

        $this->assertNull($ficha->fichaVem);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => $ficha->eml_candidato]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'des_onde_estuda' => 'Escola Estadual Centro',
        ]);
    });

    test('pode atualizar restricoes de saude', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 0,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem([
                'eml_candidato' => $ficha->eml_candidato,
                'ind_restricao' => 1,
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
                'restricoes' => [$this->restricoes[0]->idt_restricao => 1],
                'complementos' => [$this->restricoes[0]->idt_restricao => 'Alergia a frutos do mar'],
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->refresh();

        $this->assertTrue($ficha->ind_restricao);
        $this->assertEquals(1, $ficha->fichaSaude->count());
    });

    test('restricoes antigas sao substituidas ao atualizar', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 1,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Restricao antiga',
        ]);

        $this->assertEquals(1, $ficha->fichaSaude->count());

        $payload = array_merge(
            dadosCandidatoVem([
                'eml_candidato' => $ficha->eml_candidato,
                'ind_restricao' => 1,
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
                'restricoes' => [
                    $this->restricoes[1]->idt_restricao => 1,
                    $this->restricoes[2]->idt_restricao => 1,
                ],
                'complementos' => [
                    $this->restricoes[1]->idt_restricao => 'Nova restricao 1',
                    $this->restricoes[2]->idt_restricao => 'Nova restricao 2',
                ],
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload);

        $ficha->refresh();

        $this->assertEquals(2, $ficha->fichaSaude->count());
        $this->assertDatabaseMissing('ficha_saude', [
            'txt_complemento' => 'Restricao antiga',
        ]);
    });

    test('falha ao atualizar ficha com dados invalidos', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->put(route('vem.update', $ficha->idt_ficha), [
            'nom_candidato' => '',
            'eml_candidato' => 'email-invalido',
        ])
            ->assertSessionHasErrors(['nom_candidato', 'eml_candidato']);
    });

    test('update redireciona para listagem VEM', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => $ficha->eml_candidato]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('vem.index'));
    });
});

// ── APROVACAO ─────────────────────────────────────────────────────────────────

describe('FichaVemController - APROVACAO', function () {

    test('pode aprovar ficha VEM nao aprovada', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::NOVA,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.approve', $ficha->idt_ficha))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });

    test('pode desaprovar ficha VEM ja aprovada (toggle)', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::APROVADA,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.approve', $ficha->idt_ficha))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::NOVA);
    });

    test('aprovacao redireciona para listagem VEM', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::NOVA,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.approve', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'));
    });

    test('pode mudar situacao da ficha VEM pela rota de situacao', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::NOVA,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->post(route('vem.situacao', $ficha->idt_ficha), ['tip_situacao' => 'A'])
            ->assertSessionHas('success')
            ->assertRedirect();

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });
});

// ── EXCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaVemController - EXCLUSAO', function () {

    test('pode excluir ficha VEM com sucesso (soft delete)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('vem.destroy', $fichaId))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
    });

    test('exclusao redireciona para listagem VEM', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->delete(route('vem.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'));
    });

    test('ficha_vem permanece no banco apos soft delete da ficha', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('vem.destroy', $fichaId));

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
        $this->assertDatabaseHas('ficha_vem', ['idt_ficha' => $fichaId]);
    });

    test('restricoes de saude permanecem no banco apos soft delete', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 1,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);
        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Alergia',
        ]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('vem.destroy', $fichaId));

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
        $this->assertDatabaseHas('ficha_saude', ['idt_ficha' => $fichaId]);
    });
});

describe('FichaVemController — Redirecionamento Inteligente', function () {
    test('redireciona para a home ao salvar vindo de /vem', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'publico@email.com']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->from(route('home.ficha.vem'))
            ->post(route('vem.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para vem.index ao salvar vindo de /fichas/vem', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'admin@email.com']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->from(route('vem.create'))
            ->post(route('vem.store'), $payload)
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');
    });

    test('redireciona para a home ao atualizar vindo de /vem', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem([
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'atualizado@email.com',
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->from(route('home.ficha.vem'))
            ->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para vem.index ao atualizar vindo de /fichas/vem', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem([
                'nom_candidato' => 'Nome Admin',
                'eml_candidato' => 'admin.atualizado@email.com',
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->from(route('vem.edit', $ficha->idt_ficha))
            ->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');
    });
});
