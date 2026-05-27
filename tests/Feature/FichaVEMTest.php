<?php

use App\Models\Ficha;
use App\Models\FichaVem;
use App\Models\FichaFoto;
use App\Models\Pessoa;
use App\Models\Participante;
use App\Models\PessoaSaude;
use App\Models\PessoaFoto;
use App\Models\TipoMovimento;
use App\Models\TipoResponsavel;
use App\Models\TipoRestricao;
use App\Enums\TipoSituacao;
use App\Enums\Genero;
use App\Enums\TamanhoCamiseta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

uses(RefreshDatabase::class);

// ── Dados base reutilizáveis ──────────────────────────────────────────────────

function dadosCandidatoVem(array $overrides = []): array
{
    return array_merge([
        'tip_genero' => Genero::MASCULINO->value,
        'num_cpf_candidato' => '123.456.789-00',
        'nom_candidato' => 'Lucas Oliveira',
        'nom_apelido' => 'Luca',
        'dat_nascimento' => '2007-03-15',
        'tel_candidato' => '61999991111',
        'eml_candidato' => 'lucas@email.com',
        'des_endereco' => 'Rua das Palmeiras, 42',
        'tam_camiseta' => TamanhoCamiseta::M->value,
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
    $this->user = createUser();
    $this->actingAs($this->user);

    TipoMovimento::factory()->create([
        'idt_movimento' => TipoMovimento::VEM,
        'des_sigla' => 'VEM'
    ]);
    $this->evento = createEvento();

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
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1);
    });

    test('listagem filtra por apelido do candidato', function () {
        Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Outro Nome',
            'nom_apelido' => 'ApelidoVemUnico',
        ]);

        $this->get(route('vem.index', ['search' => 'ApelidoVemUnico']))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1);
    });

    test('listagem filtra por evento', function () {
        $outroEvento = createEvento();
        $ficha1 = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Ficha Evento 1']);
        FichaVem::factory()->create(['idt_ficha' => $ficha1->idt_ficha]);

        $ficha2 = Ficha::factory()->create(['idt_evento' => $outroEvento->idt_evento, 'nom_candidato' => 'Ficha Evento 2']);
        FichaVem::factory()->create(['idt_ficha' => $ficha2->idt_ficha]);

        $this->get(route('vem.index', ['evento' => $this->evento->idt_evento]))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1)
            ->assertViewHas('evento', fn ($ev) => $ev->idt_evento === $this->evento->idt_evento);
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
        $response->assertViewHas('movimentopadrao', TipoMovimento::VEM);
        $response->assertViewHas('responsaveis');
        $response->assertViewHas('restricoes');
    });

    test('pode visualizar ficha VEM existente', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.show', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formVEM')
            ->assertViewHas('ficha')
            ->assertViewHas('eventos')
            ->assertViewHas('movimentopadrao', TipoMovimento::VEM)
            ->assertViewHas('responsaveis')
            ->assertViewHas('restricoes');
    });

    test('visualizar ficha VEM no modo impressao', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.show', [$ficha->idt_ficha, 'print' => 1]))
            ->assertStatus(200)
            ->assertViewIs('ficha.print')
            ->assertViewHas('ficha')
            ->assertViewHas('tipo', 'VEM')
            ->assertViewHas('rotaEdit');
    });

    test('pode acessar formulario de edicao', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('vem.edit', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formVEM')
            ->assertViewHas('ficha')
            ->assertViewHas('eventos')
            ->assertViewHas('movimentopadrao', TipoMovimento::VEM)
            ->assertViewHas('responsaveis')
            ->assertViewHas('restricoes');
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
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'lucas@email.com')->first();

        expect($ficha)->not->toBeNull();
        expect($ficha->usu_inclusao)->toBe($this->user->id);
        expect($ficha->usu_alteracao)->toBe($this->user->id);

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'des_onde_estuda' => 'Escola Estadual Centro',
            'des_mora_quem' => 'Pais',
        ]);
    });

    test('pode criar ficha VEM com apenas pai preenchido', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'pai_only@email.com', 'num_cpf_candidato' => '111.111.111-11']),
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
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'pai_only@email.com')->first();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_pai' => 'Jose Oliveira',
        ]);
    });

    test('pode criar ficha VEM com apenas responsavel preenchido', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'resp_only@email.com', 'num_cpf_candidato' => '222.222.222-22']),
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
            ->assertRedirect(route('home'))
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
                'num_cpf_candidato' => '333.333.333-33',
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
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'restricao@email.com')->first();

        expect($ficha->fichaSaude)->toHaveCount(2);
    });

    test('pode criar ficha VEM com candidato crismado e paroquia', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'crismado@email.com', 'num_cpf_candidato' => '444.444.444-44']),
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
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'crismado@email.com')->first();

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
            'ind_crismado' => true,
            'nom_paroquia' => 'Paroquia Nossa Senhora',
        ]);
    });

    test('pode criar ficha VEM com upload de foto', function () {
        Storage::fake('public');
        // Usar UploadedFile::fake()->create para não requerer a extensão GD
        $foto = UploadedFile::fake()->create('candidato.jpg', 100);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'foto@email.com', 'num_cpf_candidato' => '555.555.555-55']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
                'med_foto' => $foto,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'foto@email.com')->first();

        expect($ficha->foto)->not->toBeNull();
        Storage::disk('public')->assertExists($ficha->foto->med_foto);
    });

    test('nao cria ficha_vem quando nenhum responsavel e informado', function () {
        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => 'sem_resp@email.com', 'num_cpf_candidato' => '666.666.666-66']),
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
            dadosCandidatoVem(['eml_candidato' => 'sem_vem@email.com', 'num_cpf_candidato' => '777.777.777-77']),
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

    test('falha ao criar ficha com CPF duplicado', function () {
        Ficha::factory()->create([
            'num_cpf_candidato' => '12345678901',
        ]);

        $payload = array_merge(
            dadosCandidatoVem(['num_cpf_candidato' => '12345678901']),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertStatus(302)
            ->assertSessionHasErrors(['num_cpf_candidato']);
    });

    test('tratamento de erro geral Throwable ao salvar', function () {
        // Registrar um event listener temporário na criação da Ficha para disparar exceção
        Ficha::creating(function ($ficha) {
            throw new \Exception('Erro interno simulado do banco de dados');
        });

        $payload = array_merge(
            dadosCandidatoVem(),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->post(route('vem.store'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('error', 'Ocorreu um erro ao salvar a ficha. Tente novamente.');

        // Limpar o event listener para evitar contágio em outros testes
        Ficha::flushEventListeners();
    });
});

// ── ALTERACAO ─────────────────────────────────────────────────────────────────

describe('FichaVemController - ALTERACAO', function () {

    test('pode atualizar dados do candidato e VEM', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Nome Original',
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosCandidatoVem([
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'atualizado@email.com',
                'tip_genero' => Genero::FEMININO->value,
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
            ]),
            dadosVem(['des_onde_estuda' => 'Escola Nova']),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->nom_candidato)->toBe('Nome Atualizado');
        expect($ficha->eml_candidato)->toBe('atualizado@email.com');
        expect($ficha->fichaVem->des_onde_estuda)->toBe('Escola Nova');
        expect($ficha->usu_alteracao)->toBe($this->user->id);
    });

    test('cria ficha_vem ao atualizar ficha que nao tinha', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);

        $this->assertNull($ficha->fichaVem);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => $ficha->eml_candidato, 'num_cpf_candidato' => $ficha->num_cpf_candidato]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('vem.index'))
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
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
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

    test('restricoes antigas sao deletadas ao atualizar com ind_restricao 0', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 1,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Restricao antiga',
        ]);

        $payload = array_merge(
            dadosCandidatoVem([
                'eml_candidato' => $ficha->eml_candidato,
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'ind_restricao' => 0,
            ]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload);

        $ficha->refresh();

        $this->assertFalse($ficha->ind_restricao);
        $this->assertEquals(0, $ficha->fichaSaude->count());
    });

    test('pode atualizar enviando nova foto', function () {
        Storage::fake('public');
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Usar UploadedFile::fake()->create para não requerer GD
        $novaFoto = UploadedFile::fake()->create('nova.jpg', 100);

        $payload = array_merge(
            dadosCandidatoVem(['eml_candidato' => $ficha->eml_candidato, 'num_cpf_candidato' => $ficha->num_cpf_candidato]),
            dadosVem(),
            [
                'idt_evento' => $this->evento->idt_evento,
                'idt_falar_com' => $this->responsavel->idt_responsavel,
                'med_foto' => $novaFoto,
            ]
        );

        $this->put(route('vem.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('vem.index'));

        $ficha->refresh();
        expect($ficha->foto)->not->toBeNull();
        Storage::disk('public')->assertExists($ficha->foto->med_foto);
    });
});

// ── EXCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaVemController - EXCLUSAO', function () {

    test('pode excluir ficha VEM com sucesso (soft delete)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('vem.destroy', $fichaId))
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
    });

    test('tratamento de QueryException de integridade referencial (codigo 23000)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Usar uma classe anônima herdando de Exception para expor a propriedade $code como string '23000'
        $innerException = new class('integrity constraint violation') extends \Exception {
            protected $code = '23000';
        };

        // Registrar listener temporário de deleting que levanta uma QueryException (integridade referencial 23000)
        Ficha::deleting(function ($ficha) use ($innerException) {
            throw new QueryException(
                'mysql',
                'delete from ficha where idt_ficha = ?',
                [$ficha->idt_ficha],
                $innerException
            );
        });

        $this->delete(route('vem.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('error', 'Não é possível excluir esta ficha. È preciso apagar os dados associados.');

        Ficha::flushEventListeners();
    });

    test('tratamento de QueryException geral no destroy', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Registrar listener de deleting que levanta uma QueryException genérica (codigo 500)
        $innerException = new class('general db error') extends \Exception {
            protected $code = '500';
        };

        Ficha::deleting(function ($ficha) use ($innerException) {
            throw new QueryException(
                'mysql',
                'delete from ficha where idt_ficha = ?',
                [$ficha->idt_ficha],
                $innerException
            );
        });

        $this->delete(route('vem.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('error', 'Erro ao tentar excluir a ficha.');

        Ficha::flushEventListeners();
    });
});

// ── APROVACAO E INTEGRACAO ────────────────────────────────────────────────────

describe('FichaVemController - APROVACAO E INTEGRACAO', function () {

    test('pode aprovar ficha VEM nao aprovada com efeitos de integracao de negocio', function () {
        Storage::fake('public');
        // Usar UploadedFile::fake()->create para não requerer GD
        $foto = UploadedFile::fake()->create('avatar.jpg', 100);

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => '999.888.777-66',
            'eml_candidato' => 'aprovacao@email.com',
            'tip_situacao' => TipoSituacao::CADASTRADO,
            'ind_restricao' => 1,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);
        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Alergia intolerante',
        ]);
        
        $path = $foto->store('fichas/fotos', 'public');
        FichaFoto::create([
            'idt_ficha' => $ficha->idt_ficha,
            'med_foto' => $path,
        ]);

        $this->get(route('vem.approve', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADO);
        expect($ficha->idt_pessoa)->not->toBeNull();

        $this->assertDatabaseHas('pessoa', [
            'idt_pessoa' => $ficha->idt_pessoa,
            'eml_pessoa' => 'aprovacao@email.com',
        ]);

        $this->assertDatabaseHas('participante', [
            'idt_pessoa' => $ficha->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
        ]);

        $this->assertDatabaseHas('pessoa_saude', [
            'idt_pessoa' => $ficha->idt_pessoa,
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Alergia intolerante',
        ]);

        $pessoaFoto = PessoaFoto::where('idt_pessoa', $ficha->idt_pessoa)->first();
        expect($pessoaFoto)->not->toBeNull();
        Storage::disk('public')->assertExists($pessoaFoto->med_foto);
    });

    test('aprovar ficha VEM sem CPF lanca excecao de integracao', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => null,
            'tip_situacao' => TipoSituacao::CADASTRADO,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Desabilitar tratamento de exceções de resposta do Laravel para que a RuntimeException original chegue ao teste
        $this->withoutExceptionHandling();

        expect(fn () => $this->get(route('vem.approve', $ficha->idt_ficha)))
            ->toThrow(\RuntimeException::class);
    });

    test('pode desaprovar ficha VEM ja aprovada (toggle) com remocao de participante', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => '555.444.333-22',
            'tip_situacao' => TipoSituacao::APROVADO,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);
        
        $pessoa = Pessoa::factory()->create([
            'num_cpf_pessoa' => '555.444.333-22',
            'eml_pessoa' => $ficha->eml_candidato,
        ]);
        $ficha->update(['idt_pessoa' => $pessoa->idt_pessoa]);
        
        Participante::create([
            'idt_pessoa' => $pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
        ]);

        $this->get(route('vem.approve', $ficha->idt_ficha))
            ->assertRedirect(route('vem.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::CADASTRADO);
        $this->assertDatabaseMissing('participante', [
            'idt_pessoa' => $pessoa->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
        ]);
    });
});

// ── SEGURANCA E AUTENTICACAO ──────────────────────────────────────────────────

describe('FichaVemController - SEGURANCA E AUTENTICACAO', function () {

    test('redireciona para login ao tentar acessar listagem sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('vem.index'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar acessar criacao sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('vem.create'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar salvar sem estar autenticado', function () {
        auth()->logout();

        $this->post(route('vem.store'), [])
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar ver sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('vem.show', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar editar sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('vem.edit', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar atualizar sem estar autenticado', function () {
        auth()->logout();

        $this->put(route('vem.update', 1), [])
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar excluir sem estar autenticado', function () {
        auth()->logout();

        $this->delete(route('vem.destroy', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar aprovar sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('vem.approve', 1))
            ->assertRedirect(route('login'));
    });
});
