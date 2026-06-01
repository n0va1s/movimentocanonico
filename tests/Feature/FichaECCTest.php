<?php

use App\Enums\TipoSituacao;
use App\Models\Ficha;
use App\Models\FichaEcc;
use App\Models\FichaEccFilho;
use App\Models\TipoMovimento;
use App\Models\TipoRestricao;
use App\Models\User;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers reutilizaveis ─────────────────────────────────────────────────────

function dadosParticipante(array $overrides = []): array
{
    return array_merge([
        'tip_genero' => 'M',
        'num_cpf_candidato' => '123.456.789-00',
        'nom_candidato' => 'Carlos Silva',
        'nom_apelido' => 'Car',
        'dat_nascimento' => '1980-01-01',
        'tel_candidato' => '(61) 99999-9999',
        'eml_candidato' => 'carlos@email.com',
        'nom_profissao' => 'Engenheiro',
        'des_endereco' => 'Rua das Flores, 123',
        'tam_camiseta' => 'M',
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
        'tip_genero_conjuge' => 'F',
        'dat_nascimento_conjuge' => '1982-01-01',
        'tel_conjuge' => '(61) 98888-8888',
        'eml_conjuge' => 'maria@email.com',
        'nom_profissao_conjuge' => 'Medica',
        'ind_catolico_conjuge' => 1,
        'tip_habilidade_conjuge' => 'A',
        'tam_camiseta_conjuge' => 'P',
        'tip_estado_civil' => 'C',
        'nom_paroquia' => 'Paroquia do Lago',
        'dat_casamento' => '2010-06-15',
        'qtd_filhos' => 0,
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

    $this->evento = Evento::factory()->create(['idt_movimento' => TipoMovimento::ECC]);

    $this->restricoes = TipoRestricao::factory()->count(2)->create();
});

// ── INCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaEccController - INCLUSAO', function () {

    test('pode acessar listagem de fichas ECC', function () {
        $this->get(route('ecc.index'))
            ->assertStatus(200)
            ->assertViewIs('ficha.listECC')
            ->assertViewHas('fichas');
    });

    test('pode filtrar listagem por nome do candidato', function () {
        Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Buscado Silva']);

        Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Outro Nome']);

        $this->get(route('ecc.index', ['search' => 'Buscado']))
            ->assertStatus(200)
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 1);
    });

    test('pode acessar formulario de criacao', function () {
        $this->get(route('ecc.create'))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC');
    });

    test('pode criar ficha ECC com dados completos do participante e conjuge', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['txt_observacao' => 'Comentario do participante']),
            dadosConjuge()
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success')
            ->assertRedirect(route('ecc.index'));

        $ficha = Ficha::where('eml_candidato', 'carlos@email.com')->first();

        $this->assertDatabaseHas('ficha', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_candidato' => 'Carlos Silva',
            'num_cpf_candidato' => '12345678900',
            'ind_consentimento' => true,
        ]);

        $this->assertDatabaseHas('ficha_ecc', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_conjuge' => 'Maria Silva',
            'num_cpf_conjuge' => '98765432100',
            'tip_estado_civil' => 'C',
        ]);
    });

    test('pode criar ficha ECC com filhos', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => 'joao@email.com', 'num_cpf_candidato' => '111.111.111-11']),
            dadosConjuge(['num_cpf_conjuge' => '222.222.222-22', 'nom_conjuge' => 'Ana', 'qtd_filhos' => 2]),
            [
                'filhos' => [
                    ['nom_filho' => 'Pedro', 'dat_nascimento_filho' => '2005-01-15'],
                    ['nom_filho' => 'Lucas', 'dat_nascimento_filho' => '2008-06-20'],
                ],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'joao@email.com')->first();

        $this->assertDatabaseHas('ficha_ecc_filho', ['nom_filho' => 'Pedro']);
        $this->assertDatabaseHas('ficha_ecc_filho', ['nom_filho' => 'Lucas']);
        $this->assertEquals(2, FichaEccFilho::where('idt_ficha', $ficha->fichaEcc->idt_ficha)->count());
    });

    test('ignora filhos com nome vazio ao criar', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['eml_candidato' => 'teste@email.com', 'num_cpf_candidato' => '100.100.100-10']),
            dadosConjuge(['num_cpf_conjuge' => '200.200.200-20']),
            [
                'filhos' => [
                    ['nom_filho' => '', 'dat_nascimento_filho' => '2005-01-15'],
                    ['nom_filho' => 'Pedro', 'dat_nascimento_filho' => '2008-06-20'],
                ],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'teste@email.com')->first();

        $this->assertEquals(1, FichaEccFilho::where('idt_ficha', $ficha->fichaEcc->idt_ficha)->count());
    });

    test('pode criar ficha ECC com restricoes de saude', function () {
        $r1 = $this->restricoes[0]->idt_restricao;
        $r2 = $this->restricoes[1]->idt_restricao;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'eml_candidato' => 'paulo@email.com',
                'num_cpf_candidato' => '333.333.333-33',
                'ind_restricao' => 1,
            ]),
            dadosConjuge(['num_cpf_conjuge' => '444.444.444-44', 'nom_conjuge' => 'Julia']),
            [
                'restricoes' => [$r1 => 1, $r2 => 1],
                'complementos' => [$r1 => 'Alergia a amendoim', $r2 => 'Sem gluten'],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'paulo@email.com')->first();

        $this->assertEquals(2, $ficha->fichaSaude->count());
    });

    test('nao cria restricoes de saude quando ind_restricao e 0', function () {
        $r1 = $this->restricoes[0]->idt_restricao;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'eml_candidato' => 'semrestricao@email.com',
                'num_cpf_candidato' => '555.555.555-55',
                'ind_restricao' => 0,
            ]),
            dadosConjuge(['num_cpf_conjuge' => '666.666.666-66']),
            [
                'restricoes' => [$r1 => 1],
                'complementos' => [$r1 => 'Deve ser ignorado'],
            ]
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'semrestricao@email.com')->first();

        $this->assertEquals(0, $ficha->fichaSaude->count());
    });

    test('falha ao criar ficha sem campos obrigatorios do participante', function () {
        $this->post(route('ecc.store'), ['idt_evento' => $this->evento->idt_evento])
            ->assertSessionHasErrors([
                'nom_candidato',
                'dat_nascimento',
                'eml_candidato',
                'des_endereco',
                'tam_camiseta',
                'ind_consentimento',
                'ind_restricao',
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

    test('falha ao criar ficha com data de nascimento invalida', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['dat_nascimento' => 'nao-e-data']),
            dadosConjuge(['num_cpf_conjuge' => '888.888.888-88'])
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHasErrors(['dat_nascimento']);
    });

    test('falha ao criar ficha com email do conjuge invalido', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(['num_cpf_candidato' => '900.900.900-90', 'eml_candidato' => 'valido@email.com']),
            dadosConjuge(['num_cpf_conjuge' => '901.901.901-90', 'eml_conjuge' => 'email-invalido'])
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHasErrors(['eml_conjuge']);
    });

    test('falha ao criar ficha com evento inexistente', function () {
        $payload = array_merge(
            ['idt_evento' => 99999],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->post(route('ecc.store'), $payload)
            ->assertSessionHasErrors(['idt_evento']);
    });
});

// ── VISUALIZACAO ─────────────────────────────────────────────────────────────

describe('FichaEccController - VISUALIZACAO', function () {

    test('pode visualizar ficha ECC existente', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $this->get(route('ecc.show', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC')
            ->assertViewHas('ficha', fn ($f) => $f->idt_ficha === $ficha->idt_ficha);
    });

    test('retorna 404 ao visualizar ficha inexistente', function () {
        $this->get(route('ecc.show', 99999))
            ->assertStatus(404);
    });

    test('pode acessar formulario de edicao', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $this->get(route('ecc.edit', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formECC')
            ->assertViewHas('ficha', fn ($f) => $f->idt_ficha === $ficha->idt_ficha);
    });

    test('retorna 404 ao editar ficha inexistente', function () {
        $this->get(route('ecc.edit', 99999))
            ->assertStatus(404);
    });
});

// ── ALTERACAO ─────────────────────────────────────────────────────────────────

describe('FichaEccController - ALTERACAO', function () {

    test('pode atualizar dados do participante', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Nome Original']);

        $ecc = $ficha->fichaEcc;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'tip_genero' => 'F',
                'nom_candidato' => 'Nome Atualizado',
                'nom_apelido' => 'Novo Apelido',
                'dat_nascimento' => '1985-01-01',
                'eml_candidato' => 'novo@email.com',
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => $ecc->nom_conjuge,
                'tip_genero_conjuge' => $ecc->tip_genero_conjuge?->value,
                'dat_nascimento_conjuge' => $ecc->dat_nascimento_conjuge->format('Y-m-d'),
                'tam_camiseta_conjuge' => $ecc->tam_camiseta_conjuge?->value,
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => $ecc->tip_estado_civil?->value,
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success')
            ->assertRedirect(route('ecc.index'));

        $ficha->refresh();

        $this->assertEquals('Nome Atualizado', $ficha->nom_candidato);
        $this->assertEquals('F', $ficha->tip_genero->value);
        $this->assertEquals('novo@email.com', $ficha->eml_candidato);
    });

    test('pode atualizar dados do conjuge', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory()->state(['nom_conjuge' => 'Nome Conjuge Original']), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $ecc = $ficha->fichaEcc;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'tip_genero' => $ficha->tip_genero?->value,
                'nom_candidato' => $ficha->nom_candidato,
                'dat_nascimento' => $ficha->dat_nascimento->format('Y-m-d'),
                'eml_candidato' => $ficha->eml_candidato,
                'tam_camiseta' => $ficha->tam_camiseta?->value,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => 'Nome Conjuge Atualizado',
                'tip_genero_conjuge' => 'F',
                'dat_nascimento_conjuge' => '1990-05-15',
                'tam_camiseta_conjuge' => 'M',
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => 'C',
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ecc->refresh();

        $this->assertEquals('Nome Conjuge Atualizado', $ecc->nom_conjuge);
    });

    test('pode adicionar filhos em ficha existente', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory()->semFilhos(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $ecc = $ficha->fichaEcc;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'tip_genero' => $ficha->tip_genero?->value,
                'nom_candidato' => $ficha->nom_candidato,
                'dat_nascimento' => $ficha->dat_nascimento->format('Y-m-d'),
                'eml_candidato' => $ficha->eml_candidato,
                'tam_camiseta' => $ficha->tam_camiseta?->value,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => $ecc->nom_conjuge,
                'tip_genero_conjuge' => $ecc->tip_genero_conjuge?->value,
                'dat_nascimento_conjuge' => $ecc->dat_nascimento_conjuge->format('Y-m-d'),
                'tam_camiseta_conjuge' => $ecc->tam_camiseta_conjuge?->value,
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => $ecc->tip_estado_civil?->value,
                'qtd_filhos' => 1,
                'filhos' => [
                    ['nom_filho' => 'Novo Filho', 'dat_nascimento_filho' => '2010-03-20'],
                ],
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        $ecc->refresh();

        $this->assertEquals(1, $ecc->filhos->count());
        $this->assertEquals('Novo Filho', $ecc->filhos->first()->nom_filho);
    });

    test('substitui todos os filhos ao atualizar ficha', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $ecc = $ficha->fichaEcc;

        FichaEccFilho::factory()->create([
            'idt_ficha' => $ecc->idt_ficha,
            'nom_filho' => 'Filho Antigo',
        ]);

        $this->assertEquals(1, $ecc->filhos->count());

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'tip_genero' => $ficha->tip_genero?->value,
                'nom_candidato' => $ficha->nom_candidato,
                'dat_nascimento' => $ficha->dat_nascimento->format('Y-m-d'),
                'eml_candidato' => $ficha->eml_candidato,
                'tam_camiseta' => $ficha->tam_camiseta?->value,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => $ecc->nom_conjuge,
                'tip_genero_conjuge' => $ecc->tip_genero_conjuge?->value,
                'dat_nascimento_conjuge' => $ecc->dat_nascimento_conjuge->format('Y-m-d'),
                'tam_camiseta_conjuge' => $ecc->tam_camiseta_conjuge?->value,
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => $ecc->tip_estado_civil?->value,
                'qtd_filhos' => 2,
                'filhos' => [
                    ['nom_filho' => 'Novo Filho 1', 'dat_nascimento_filho' => '2005-01-15'],
                    ['nom_filho' => 'Novo Filho 2', 'dat_nascimento_filho' => '2008-06-20'],
                ],
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload);

        $ecc->refresh();

        $this->assertEquals(2, $ecc->filhos->count());
        $this->assertDatabaseMissing('ficha_ecc_filho', ['nom_filho' => 'Filho Antigo']);
    });

    test('pode atualizar restricoes de saude', function () {
        $r1 = $this->restricoes[0]->idt_restricao;

        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'ind_restricao' => 0]);

        $ecc = $ficha->fichaEcc;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'tip_genero' => $ficha->tip_genero?->value,
                'nom_candidato' => $ficha->nom_candidato,
                'dat_nascimento' => $ficha->dat_nascimento->format('Y-m-d'),
                'eml_candidato' => $ficha->eml_candidato,
                'tam_camiseta' => $ficha->tam_camiseta?->value,
                'ind_restricao' => 1,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => $ecc->nom_conjuge,
                'tip_genero_conjuge' => $ecc->tip_genero_conjuge?->value,
                'dat_nascimento_conjuge' => $ecc->dat_nascimento_conjuge->format('Y-m-d'),
                'tam_camiseta_conjuge' => $ecc->tam_camiseta_conjuge?->value,
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => $ecc->tip_estado_civil?->value,
                'restricoes' => [$r1 => 1],
                'complementos' => [$r1 => 'Alergia a frutos do mar'],
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload);

        $ficha->refresh();

        $this->assertTrue($ficha->ind_restricao);
        $this->assertEquals(1, $ficha->fichaSaude->count());
    });

    test('remove restricoes ao atualizar com ind_restricao = 0', function () {
        $r1 = $this->restricoes[0]->idt_restricao;

        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'ind_restricao' => 1]);

        $ficha->fichaSaude()->create(['idt_restricao' => $r1, 'txt_complemento' => 'Alergia']);

        $ecc = $ficha->fichaEcc;

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante([
                'num_cpf_candidato' => $ficha->num_cpf_candidato,
                'tip_genero' => $ficha->tip_genero?->value,
                'nom_candidato' => $ficha->nom_candidato,
                'dat_nascimento' => $ficha->dat_nascimento->format('Y-m-d'),
                'eml_candidato' => $ficha->eml_candidato,
                'tam_camiseta' => $ficha->tam_camiseta?->value,
                'ind_restricao' => 0,
            ]),
            [
                'num_cpf_conjuge' => $ecc->num_cpf_conjuge,
                'nom_conjuge' => $ecc->nom_conjuge,
                'tip_genero_conjuge' => $ecc->tip_genero_conjuge?->value,
                'dat_nascimento_conjuge' => $ecc->dat_nascimento_conjuge->format('Y-m-d'),
                'tam_camiseta_conjuge' => $ecc->tam_camiseta_conjuge?->value,
                'tip_habilidade_conjuge' => $ecc->tip_habilidade_conjuge?->value,
                'tip_estado_civil' => $ecc->tip_estado_civil?->value,
            ]
        );

        $this->put(route('ecc.update', $ficha->idt_ficha), $payload);

        $ficha->refresh();

        $this->assertFalse($ficha->ind_restricao);
        $this->assertEquals(0, $ficha->fichaSaude->count());
    });

    test('retorna 404 ao atualizar ficha inexistente', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->put(route('ecc.update', 99999), $payload)
            ->assertStatus(404);
    });

    test('falha ao atualizar ficha sem campos obrigatorios', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $this->put(route('ecc.update', $ficha->idt_ficha), [])
            ->assertSessionHasErrors([
                'nom_candidato',
                'dat_nascimento',
                'eml_candidato',
                'nom_conjuge',
                'tip_estado_civil',
            ]);
    });
});

// ── EXCLUSAO ──────────────────────────────────────────────────────────────────

describe('FichaEccController - EXCLUSAO', function () {

    test('pode excluir ficha ECC com sucesso (soft delete)', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $fichaId = $ficha->idt_ficha;

        $this->delete(route('ecc.destroy', $fichaId))
            ->assertSessionHas('success')
            ->assertRedirect(route('ecc.index'));

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $fichaId]);
    });

    test('ficha excluida nao aparece na listagem', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Excluido']);

        $this->delete(route('ecc.destroy', $ficha->idt_ficha));

        $this->get(route('ecc.index'))
            ->assertViewHas('fichas', fn ($fichas) => $fichas->total() === 0);
    });

    test('deleta em cascata filhos ao remover ficha', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $ecc = $ficha->fichaEcc;

        $filho = FichaEccFilho::factory()->create(['idt_ficha' => $ecc->idt_ficha]);

        $this->delete(route('ecc.destroy', $ficha->idt_ficha));

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $ficha->idt_ficha]);
        $this->assertDatabaseHas('ficha_ecc_filho', ['idt_filho' => $filho->idt_filho]);
    });

    test('retorna 404 ao excluir ficha inexistente', function () {
        $this->delete(route('ecc.destroy', 99999))
            ->assertStatus(404);
    });
});

// ── APROVACAO ─────────────────────────────────────────────────────────────────

describe('FichaEccController - APROVACAO', function () {

    test('pode aprovar ficha ECC', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'tip_situacao' => TipoSituacao::NOVA]);

        $this->get(route('ecc.approve', $ficha->idt_ficha))
            ->assertSessionHas('success')
            ->assertRedirect(route('ecc.index'));

        $ficha->refresh();

        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });

    test('pode mudar situacao da ficha ECC pela rota de situacao', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento, 'tip_situacao' => TipoSituacao::NOVA]);

        $this->post(route('ecc.situacao', $ficha->idt_ficha), ['tip_situacao' => 'A'])
            ->assertSessionHas('success')
            ->assertRedirect();

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });
});

// ── AUTENTICACAO ──────────────────────────────────────────────────────────────

describe('FichaEccController - AUTENTICACAO', function () {

    test('redireciona para login ao acessar listagem sem autenticacao', function () {
        auth()->logout();

        $this->get(route('ecc.index'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar criar ficha sem autenticacao', function () {
        auth()->logout();

        $this->post(route('ecc.store'), [])
            ->assertRedirect(route('login'));
    });
});

describe('FichaEccController — Redirecionamento Inteligente', function () {
    test('redireciona para a home ao salvar vindo de /ecc', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'publico@email.com'],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->from(route('home.ficha.ecc'))
            ->post(route('ecc.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para ecc.index ao salvar vindo de /fichas/ecc', function () {
        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'admin@email.com'],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->from(route('ecc.create'))
            ->post(route('ecc.store'), $payload)
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');
    });

    test('redireciona para a home ao atualizar vindo de /ecc', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Nome Atualizado', 'eml_candidato' => 'atualizado@email.com'],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->from(route('home.ficha.ecc'))
            ->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para ecc.index ao atualizar vindo de /fichas/ecc', function () {
        $ficha = Ficha::factory()
            ->has(FichaEcc::factory(), 'fichaEcc')
            ->create(['idt_evento' => $this->evento->idt_evento]);

        $payload = array_merge(
            ['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => 'Nome Admin', 'eml_candidato' => 'admin.atualizado@email.com'],
            dadosParticipante(),
            dadosConjuge()
        );

        $this->from(route('ecc.edit', $ficha->idt_ficha))
            ->put(route('ecc.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('ecc.index'))
            ->assertSessionHas('success');
    });
});
