<?php

use App\Models\Ficha;
use App\Models\FichaEcc;
use App\Models\FichaEccFilho;
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

// ── Helpers reutilizáveis ─────────────────────────────────────────────────────

function dadosParticipante(array $overrides = []): array
{
    return array_merge([
        'tip_genero' => Genero::MASCULINO->value,
        'num_cpf_candidato' => '123.456.789-00',
        'nom_candidato' => 'Carlos Silva',
        'nom_apelido' => 'Car',
        'dat_nascimento' => '1980-01-01',
        'tel_candidato' => '61999999999',
        'eml_candidato' => 'carlos@email.com',
        'nom_profissao' => 'Engenheiro',
        'des_endereco' => 'Rua das Flores, 123',
        'tam_camiseta' => TamanhoCamiseta::M->value,
        'tip_como_soube' => 'IND',
        'tip_habilidade' => 'A',
        'ind_catolico' => 1,
        'ind_toca_instrumento' => 0,
        'ind_consentimento' => 1,
        'ind_restricao' => 0,
        'txt_observacao' => null,
    ], $overrides);
}

function dadosConjuge(array $overrides = []): array
{
    return array_merge([
        'num_cpf_conjuge' => '987.654.321-00',
        'nom_conjuge' => 'Maria Silva',
        'nom_apelido_conjuge' => 'Mari',
        'tip_genero_conjuge' => Genero::FEMININO->value,
        'dat_nascimento_conjuge' => '1982-01-01',
        'tel_conjuge' => '61988888888',
        'eml_conjuge' => 'maria@email.com',
        'nom_profissao_conjuge' => 'Medica',
        'ind_catolico_conjuge' => 1,
        'tip_habilidade_conjuge' => 'A',
        'tam_camiseta_conjuge' => TamanhoCamiseta::P->value,
        'tip_estado_civil' => 'C',
        'nom_paroquia' => 'Paroquia do Lago',
        'dat_casamento' => '2010-06-15',
        'qtd_filhos' => 0,
    ], $overrides);
}

// ── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);

    TipoMovimento::factory()->create([
        'idt_movimento' => TipoMovimento::ECC,
        'des_sigla' => 'ECC'
    ]);
    $this->evento = createEvento();

    $this->restricoes = TipoRestricao::factory()->count(3)->create();
});

// ── LISTAGEM E FORMULARIOS ────────────────────────────────────────────────────

describe('FichaEccController - LISTAGEM E FORMULARIOS', function () {

    test('pode acessar listagem de fichas ECC', function () {
        $this->get(route('ecc.index'))
            ->assertStatus(200)
            ->assertViewIs('ficha.listECC')
            ->assertViewHas('fichas');
    });

    test('listagem retorna apenas fichas do movimento ECC', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $response = $this->get(route('ecc.index'));

        $response->assertStatus(200);
        $fichas = $response->viewData('fichas');
        expect($fichas->total())->toBeGreaterThanOrEqual(1);
    });

    test('listagem filtra por nome do candidato', function () {
        Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Buscado Silva',
        ]);

        $this->get(route('ecc.index', ['search' => 'Buscado']))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1);
    });

    test('listagem filtra por apelido do candidato', function () {
        Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Outro Nome',
            'nom_apelido' => 'ApelidoEccUnico',
        ]);

        $this->get(route('ecc.index', ['search' => 'ApelidoEccUnico']))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1);
    });

    test('listagem filtra por evento', function () {
        $outroEvento = createEvento();
        $ficha1 = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Ficha Evento 1']);
        FichaEcc::factory()->create(['idt_ficha' => $ficha1->idt_ficha]);

        $ficha2 = Ficha::factory()->create(['idt_evento' => $outroEvento->idt_evento, 'nom_candidato' => 'Ficha Evento 2']);
        FichaEcc::factory()->create(['idt_ficha' => $ficha2->idt_ficha]);

        $this->get(route('ecc.index', ['evento' => $this->evento->idt_evento]))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1)
            ->assertViewHas('evento', fn ($ev) => $ev->idt_evento === $this->evento->idt_evento);
    });

    test('pode acessar formulario de criacao', function () {
        $this->get(route('ecc.create'))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC');
    });

    test('formulario de criacao contem dados necessarios na view', function () {
        $response = $this->get(route('ecc.create'));

        $response->assertStatus(200);
        $response->assertViewHas('ficha');
        $response->assertViewHas('eventos');
        $response->assertViewHas('movimentopadrao', TipoMovimento::ECC);
        $response->assertViewHas('responsaveis');
        $response->assertViewHas('restricoes');
    });

    test('pode visualizar ficha ECC existente', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('ecc.show', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC')
            ->assertViewHas('ficha')
            ->assertViewHas('eventos')
            ->assertViewHas('movimentopadrao', TipoMovimento::ECC)
            ->assertViewHas('responsaveis')
            ->assertViewHas('restricoes');
    });

    test('visualizar ficha ECC no modo impressao', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('ecc.show', [$ficha->idt_ficha, 'print' => 1]))
            ->assertStatus(200)
            ->assertViewIs('ficha.print')
            ->assertViewHas('ficha')
            ->assertViewHas('tipo', 'ECC')
            ->assertViewHas('rotaEdit');
    });

    test('pode acessar formulario de edicao', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('ecc.edit', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC')
            ->assertViewHas('ficha')
            ->assertViewHas('eventos')
            ->assertViewHas('movimentopadrao', TipoMovimento::ECC)
            ->assertViewHas('responsaveis')
            ->assertViewHas('restricoes');
    });
});

// ── INCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaEccController - INCLUSAO', function () {

    test('pode criar ficha ECC com dados do candidato e conjuge', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->post(route('ecc.store'), $payload)
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'carlos@email.com')->first();

        expect($ficha)->not->toBeNull();
        expect($ficha->usu_inclusao)->toBe($this->user->id);
        expect($ficha->usu_alteracao)->toBe($this->user->id);

        $this->assertDatabaseHas('ficha', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_candidato' => 'Carlos Silva',
        ]);

        $this->assertDatabaseHas('ficha_ecc', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_conjuge' => 'Maria Silva',
        ]);
    });

    test('pode criar ficha ECC com filhos', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => 'joao@email.com', 'num_cpf_candidato' => '111.111.111-11']),
            dadosConjuge(['num_cpf_conjuge' => '222.222.222-22', 'nom_conjuge' => 'Ana', 'qtd_filhos' => 2]),
            [
                'filhos' => [
                    ['nom_filho' => 'Pedro', 'dat_nascimento_filho' => '2005-01-15', 'num_cpf_filho' => '111.111.111-11'],
                    ['nom_filho' => 'Lucas', 'dat_nascimento_filho' => '2008-06-20', 'num_cpf_filho' => '222.222.222-22'],
                ],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'joao@email.com')->first();

        $this->assertDatabaseHas('ficha_ecc_filho', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_filho' => 'Pedro'
        ]);
        $this->assertDatabaseHas('ficha_ecc_filho', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_filho' => 'Lucas'
        ]);
        $this->assertEquals(2, FichaEccFilho::where('idt_ficha', $ficha->idt_ficha)->count());
    });

    test('ignora filhos com nome vazio ao criar', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => 'teste@email.com', 'num_cpf_candidato' => '100.100.100-10']),
            dadosConjuge(['num_cpf_conjuge' => '200.200.200-20']),
            [
                'filhos' => [
                    ['nom_filho' => '', 'dat_nascimento_filho' => '2005-01-15', 'num_cpf_filho' => '111.111.111-11'],
                    ['nom_filho' => 'Pedro', 'dat_nascimento_filho' => '2008-06-20', 'num_cpf_filho' => '222.222.222-22'],
                ],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'teste@email.com')->first();

        $this->assertEquals(1, FichaEccFilho::where('idt_ficha', $ficha->idt_ficha)->count());
    });

    test('pode criar ficha ECC com restricoes de saude', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'eml_candidato' => 'paulo@email.com',
                'num_cpf_candidato' => '333.333.333-33',
                'ind_restricao' => 1,
            ]),
            dadosConjuge(['num_cpf_conjuge' => '444.444.444-44', 'nom_conjuge' => 'Julia']),
            [
                'restricoes' => [
                    $this->restricoes[0]->idt_restricao => true,
                    $this->restricoes[1]->idt_restricao => true,
                ],
                'complementos' => [
                    $this->restricoes[0]->idt_restricao => 'Alergia a amendoim',
                    $this->restricoes[1]->idt_restricao => 'Sem gluten',
                ],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'paulo@email.com')->first();

        $this->assertEquals(2, $ficha->fichaSaude->count());
    });

    test('pode criar ficha ECC com upload de foto do candidato e conjuge', function () {
        Storage::fake('public');
        $fotoCand = UploadedFile::fake()->create('cand.jpg', 100);
        $fotoConj = UploadedFile::fake()->create('conj.jpg', 100);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => 'foto@email.com', 'num_cpf_candidato' => '555.555.555-55']),
            dadosConjuge(['num_cpf_conjuge' => '666.666.666-66']),
            [
                'med_foto' => $fotoCand,
                'med_conjuge' => $fotoConj,
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'foto@email.com')->first();

        expect($ficha->foto)->not->toBeNull();
        Storage::disk('public')->assertExists($ficha->foto->med_foto);
        Storage::disk('public')->assertExists($ficha->foto->med_conjuge);
    });

    test('falha ao criar ficha sem campos obrigatorios do participante', function () {
        $this->post(route('ecc.store'), ['idt_evento' => $this->evento->idt_evento])
            ->assertSessionHasErrors([
                'nom_candidato',
                'dat_nascimento',
                'eml_candidato',
                'tam_camiseta',
                'ind_consentimento',
            ]);
    });

    test('falha ao criar ficha sem dados obrigatorios do conjuge', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['num_cpf_candidato' => '555.555.555-55', 'eml_candidato' => 'marcos@email.com'])
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHasErrors([
                'num_cpf_conjuge',
                'nom_conjuge',
                'tip_genero_conjuge',
                'dat_nascimento_conjuge',
                'tam_camiseta_conjuge',
                'tip_estado_civil',
            ]);
    });

    test('falha ao criar ficha sem consentimento', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['ind_consentimento' => 0]),
            dadosConjuge(['num_cpf_conjuge' => '777.777.777-77'])
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHasErrors(['ind_consentimento']);
    });

    test('tratamento de erro geral Throwable ao salvar', function () {
        Ficha::creating(function ($ficha) {
            throw new \Exception('Erro simulado de banco');
        });

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(),
            dadosConjuge()
        );

        // O FichaEccController não possui try-catch na criação (store), então lança 500
        $this->get(route('ecc.create'));
        $this->withoutExceptionHandling();

        expect(fn () => $this->post(route('ecc.store'), $payload))
            ->toThrow(\Exception::class);

        Ficha::flushEventListeners();
    });
});

// ── ALTERACAO ─────────────────────────────────────────────────────────────────

describe('FichaEccController - ALTERACAO', function () {

    test('pode atualizar dados do participante, conjuge e comum', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Nome Original',
        ]);
        $ecc = FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'nom_conjuge' => 'Maria Antiga',
        ]);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'carlos.novo@email.com',
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
            ]),
            dadosConjuge([
                'nom_conjuge' => 'Maria Atualizada',
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
            ])
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->nom_candidato)->toBe('Nome Atualizado');
        expect($ficha->eml_candidato)->toBe('carlos.novo@email.com');
        expect($ficha->fichaEcc->nom_conjuge)->toBe('Maria Atualizada');
        expect($ficha->usu_alteracao)->toBe($this->user->id);
    });

    test('cria ficha_ecc ao atualizar ficha que nao tinha', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);

        $this->assertNull($ficha->fichaEcc);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => $ficha->eml_candidato, 'num_cpf_candidato' => $ficha->num_cpf_candidato]),
            dadosConjuge()
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->fichaEcc)->not->toBeNull();
        expect($ficha->fichaEcc->nom_conjuge)->toBe('Maria Silva');
    });

    test('pode substituir filhos ao atualizar ficha', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        $ecc = FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        FichaEccFilho::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'nom_filho' => 'Filho Antigo',
        ]);

        expect(FichaEccFilho::where('idt_ficha', $ficha->idt_ficha)->count())->toBe(1);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => $ficha->eml_candidato, 'num_cpf_candidato' => $ficha->num_cpf_candidato]),
            dadosConjuge(['num_cpf_conjuge' => $ecc->num_cpf_conjuge]),
            [
                'filhos' => [
                    ['nom_filho' => 'Novo Filho 1', 'dat_nascimento_filho' => '2005-01-15', 'num_cpf_filho' => '111.111.111-11'],
                    ['nom_filho' => 'Novo Filho 2', 'dat_nascimento_filho' => '2008-06-20', 'num_cpf_filho' => '222.222.222-22'],
                ],
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $this->assertEquals(2, FichaEccFilho::where('idt_ficha', $ficha->idt_ficha)->count());
        $this->assertDatabaseMissing('ficha_ecc_filho', ['nom_filho' => 'Filho Antigo']);
    });

    test('pode atualizar restricoes de saude', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 0,
        ]);
        $ecc = FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'eml_candidato' => $ficha->eml_candidato,
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'ind_restricao' => 1,
            ]),
            dadosConjuge(['num_cpf_conjuge' => $ecc->num_cpf_conjuge]),
            [
                'restricoes' => [$this->restricoes[0]->idt_restricao => 1],
                'complementos' => [$this->restricoes[0]->idt_restricao => 'Alergia a frutos do mar'],
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->ind_restricao)->toBeTrue();
        expect($ficha->fichaSaude)->toHaveCount(1);
    });

    test('limpa restricoes ao atualizar com ind_restricao = 0', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => 1,
        ]);
        $ecc = FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);
        $ficha->fichaSaude()->create(['idt_restricao' => $this->restricoes[0]->idt_restricao, 'txt_complemento' => 'Alergia']);

        expect($ficha->fichaSaude)->toHaveCount(1);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'eml_candidato' => $ficha->eml_candidato,
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'ind_restricao' => 0,
            ]),
            dadosConjuge(['num_cpf_conjuge' => $ecc->num_cpf_conjuge])
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload);

        $ficha->refresh();
        expect($ficha->ind_restricao)->toBeFalse();
        expect($ficha->fichaSaude)->toHaveCount(0);
    });

    test('pode atualizar enviando novas fotos de candidato e conjuge', function () {
        Storage::fake('public');
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        $ecc = FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $novaCand = UploadedFile::fake()->create('cand_nova.jpg', 100);
        $novaConj = UploadedFile::fake()->create('conj_nova.jpg', 100);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => $ficha->eml_candidato, 'num_cpf_candidato' => $ficha->num_cpf_candidato]),
            dadosConjuge(['num_cpf_conjuge' => $ecc->num_cpf_conjuge]),
            [
                'med_foto' => $novaCand,
                'med_conjuge' => $novaConj,
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->foto)->not->toBeNull();
        Storage::disk('public')->assertExists($ficha->foto->med_foto);
        Storage::disk('public')->assertExists($ficha->foto->med_conjuge);
    });
});

// ── EXCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaEccController - EXCLUSAO', function () {

    test('pode excluir ficha ECC com sucesso (soft delete)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('ecc.destroy', $fichaId))
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
    });

    test('tratamento de QueryException de integridade referencial (codigo 23000)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Classe anônima para injetar código como string '23000'
        $innerException = new class('integrity constraint violation') extends \Exception {
            protected $code = '23000';
        };

        Ficha::deleting(function ($ficha) use ($innerException) {
            throw new QueryException(
                'mysql',
                'delete from ficha where idt_ficha = ?',
                [$ficha->idt_ficha],
                $innerException
            );
        });

        $this->delete(route('ecc.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('error', 'Não é possível excluir esta ficha. É preciso apagar os dados associados.');

        Ficha::flushEventListeners();
    });

    test('tratamento de QueryException geral no destroy', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

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

        $this->delete(route('ecc.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('error', 'Erro ao tentar excluir a ficha.');

        Ficha::flushEventListeners();
    });
});

// ── APROVACAO E INTEGRACAO ────────────────────────────────────────────────────

describe('FichaEccController - APROVACAO E INTEGRACAO', function () {

    test('pode aprovar ficha ECC nao aprovada com efeitos de integracao de negocio de casal e filhos', function () {
        Storage::fake('public');
        $foto = UploadedFile::fake()->create('candidato_avatar.jpg', 100);

        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => '999.888.777-66',
            'eml_candidato' => 'carlos.ecc@email.com',
            'tip_situacao' => TipoSituacao::CADASTRADO,
            'ind_restricao' => 1,
        ]);
        $fichaEcc = FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'num_cpf_conjuge' => '888.777.666-55',
            'eml_conjuge' => 'maria.ecc@email.com',
        ]);
        
        // 2 filhos com CPF
        FichaEccFilho::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'nom_filho' => 'Filho Um',
            'num_cpf_filho' => '777.666.555-44',
            'eml_filho' => 'filho1@email.com',
        ]);
        FichaEccFilho::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'nom_filho' => 'Filho Dois',
            'num_cpf_filho' => '666.555.444-33',
            'eml_filho' => 'filho2@email.com',
        ]);

        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Alergia frutos do mar',
        ]);

        $path = $foto->store('fichas/fotos', 'public');
        FichaFoto::create([
            'idt_ficha' => $ficha->idt_ficha,
            'med_foto' => $path,
        ]);

        $this->get(route('ecc.approve', $ficha->idt_ficha))
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADO);
        expect($ficha->idt_pessoa)->not->toBeNull();
        expect($ficha->fichaEcc->idt_pessoa)->not->toBeNull();

        // 1. Pessoa criada para o Candidato
        $this->assertDatabaseHas('pessoa', [
            'idt_pessoa' => $ficha->idt_pessoa,
            'eml_pessoa' => 'carlos.ecc@email.com',
        ]);

        // 2. Pessoa criada para o Cônjuge
        $this->assertDatabaseHas('pessoa', [
            'idt_pessoa' => $ficha->fichaEcc->idt_pessoa,
            'eml_pessoa' => 'maria.ecc@email.com',
        ]);

        // 3. Pessoa criada para os Filhos com CPF
        $this->assertDatabaseHas('pessoa', [
            'num_cpf_pessoa' => '777.666.555-44',
            'eml_pessoa' => 'filho1@email.com',
        ]);
        $this->assertDatabaseHas('pessoa', [
            'num_cpf_pessoa' => '666.555.444-33',
            'eml_pessoa' => 'filho2@email.com',
        ]);

        // 4. Participantes criados no evento (candidato, cônjuge, 2 filhos = 4)
        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(4);

        // 5. Restrições de saúde duplicadas em PessoaSaude para Candidato e Cônjuge
        $this->assertDatabaseHas('pessoa_saude', [
            'idt_pessoa' => $ficha->idt_pessoa,
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
        ]);
        $this->assertDatabaseHas('pessoa_saude', [
            'idt_pessoa' => $ficha->fichaEcc->idt_pessoa,
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
        ]);

        // 6. Sincronização da foto do candidato
        $pessoaFoto = PessoaFoto::where('idt_pessoa', $ficha->idt_pessoa)->first();
        expect($pessoaFoto)->not->toBeNull();
        Storage::disk('public')->assertExists($pessoaFoto->med_foto);
    });

    test('aprovar ficha ECC sem CPF do candidato lanca excecao de integracao', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => null,
            'tip_situacao' => TipoSituacao::CADASTRADO,
        ]);
        FichaEcc::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->withoutExceptionHandling();

        expect(fn () => $this->get(route('ecc.approve', $ficha->idt_ficha)))
            ->toThrow(\RuntimeException::class);
    });

    test('pode desaprovar ficha ECC ja aprovada (toggle) com remocao de participantes de casal e filhos', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'num_cpf_candidato' => '555.444.333-22',
            'tip_situacao' => TipoSituacao::APROVADO,
        ]);
        $fichaEcc = FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
            'num_cpf_conjuge' => '444.333.222-11',
        ]);
        
        $pessoaCand = Pessoa::factory()->create(['num_cpf_pessoa' => '555.444.333-22', 'eml_pessoa' => $ficha->eml_candidato]);
        $pessoaConj = Pessoa::factory()->create(['num_cpf_pessoa' => '444.333.222-11', 'eml_pessoa' => $fichaEcc->eml_conjuge]);
        $ficha->update(['idt_pessoa' => $pessoaCand->idt_pessoa]);
        $fichaEcc->update(['idt_pessoa' => $pessoaConj->idt_pessoa]);

        Participante::create(['idt_pessoa' => $pessoaCand->idt_pessoa, 'idt_evento' => $this->evento->idt_evento]);
        Participante::create(['idt_pessoa' => $pessoaConj->idt_pessoa, 'idt_evento' => $this->evento->idt_evento]);

        $this->get(route('ecc.approve', $ficha->idt_ficha))
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::CADASTRADO);

        $this->assertDatabaseMissing('participante', [
            'idt_pessoa' => $pessoaCand->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
        ]);
        $this->assertDatabaseMissing('participante', [
            'idt_pessoa' => $pessoaConj->idt_pessoa,
            'idt_evento' => $this->evento->idt_evento,
        ]);
    });
});

// ── SEGURANCA E AUTENTICACAO ──────────────────────────────────────────────────

describe('FichaEccController - SEGURANCA E AUTENTICACAO', function () {

    test('redireciona para login ao tentar acessar listagem sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('ecc.index'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar acessar criacao sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('ecc.create'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar salvar sem estar autenticado', function () {
        auth()->logout();

        $this->post(route('ecc.store'), [])
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar ver sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('ecc.show', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar editar sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('ecc.edit', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar atualizar sem estar autenticado', function () {
        auth()->logout();

        $this->put(route('ecc.update', 1), [])
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar excluir sem estar autenticado', function () {
        auth()->logout();

        $this->delete(route('ecc.destroy', 1))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar aprovar sem estar autenticado', function () {
        auth()->logout();

        $this->get(route('ecc.approve', 1))
            ->assertRedirect(route('login'));
    });
});
