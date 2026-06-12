<?php

use App\Enums\TipoSituacao;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaSGM;
use App\Models\TipoMovimento;
use App\Models\TipoResponsavel;
use App\Models\TipoRestricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers locais ────────────────────────────────────────────────────────────

/**
 * Cria um utilizador com perfil admin (necessário para aceder às rotas SGM).
 */
function criarAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * Campos obrigatórios da tabela `ficha` (base comum a todos os movimentos).
 */
function dadosFichaBase(array $overrides = []): array
{
    return array_merge([
        'tip_genero' => 'M',
        'nom_candidato' => 'João Segue-Me',
        'nom_apelido' => 'João',
        'dat_nascimento' => '2000-06-15',
        'tel_candidato' => '(61) 99999-0001',
        'eml_candidato' => 'joao.sgm@email.com',
        'nom_profissao' => 'Estudante',
        'des_endereco' => 'Quadra 5, Bloco A, Apt 101',
        'tam_camiseta' => 'M',
        'tip_como_soube' => 'IND',
        'tip_habilidade' => 'A',
        'ind_catolico' => 1,
        'ind_toca_instrumento' => 0,
        'ind_consentimento' => 1,
        'ind_restricao' => 0,
    ], $overrides);
}

/**
 * Campos obrigatórios da tabela `ficha_sgm`.
 */
function dadosSGMBase(int $idtFalarCom, array $overrides = []): array
{
    return array_merge([
        'idt_falar_com' => $idtFalarCom,
        'nom_mae' => 'Maria Mãe',
        'tel_mae' => '(61) 98888-0001',
        'eml_mae' => 'mae@email.com',
        'des_naturalidade' => 'Brasília - DF',
        'tip_escolaridade' => 'S',
        'tip_escolaridade_situacao' => 'C',
        'des_curso' => 'Direito',
        'nom_instituicao' => 'UnB',
        'tip_religiao' => 'C',
        'nom_paroquia' => 'Nossa Senhora do Lago',
        'ind_batismo' => 1,
        'ind_eucaristia' => 1,
        'ind_crisma' => 0,
    ], $overrides);
}

// ── Setup ─────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->admin = criarAdmin();
    $this->actingAs($this->admin);

    // Cria os movimentos com IDs fixos (ECC=1, VEM=2, Segue-Me=3)
    // para corresponder às constantes TipoMovimento::ECC/VEM/SegueMe
    DB::table('tipo_movimento')->insertOrIgnore([
        ['idt_movimento' => 1, 'nom_movimento' => 'Encontro de Casais com Cristo', 'des_sigla' => 'ECC', 'dat_inicio' => '1980-01-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 2, 'nom_movimento' => 'Encontro de Adolescentes com Cristo', 'des_sigla' => 'VEM', 'dat_inicio' => '2000-07-01', 'created_at' => now(), 'updated_at' => now()],
        ['idt_movimento' => 3, 'nom_movimento' => 'Encontro de Jovens com Cristo', 'des_sigla' => 'Segue-Me', 'dat_inicio' => '1990-12-31', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->evento = Evento::factory()->create(['idt_movimento' => TipoMovimento::SGM]);
    $this->responsavel = TipoResponsavel::factory()->create();
    $this->restricoes = TipoRestricao::factory()->count(2)->create();
});

// ── Acesso não autenticado ────────────────────────────────────────────────────

describe('Acesso não autenticado', function () {

    test('redireciona para login ao tentar aceder à listagem sem autenticação', function () {
        auth()->logout();

        $this->get(route('sgm.index'))
            ->assertRedirect(route('login'));
    });

    test('redireciona para login ao tentar aceder ao formulário sem autenticação', function () {
        auth()->logout();

        $this->get(route('sgm.create'))
            ->assertRedirect(route('login'));
    });
});

// ── Acesso sem permissão (role insuficiente) ──────────────────────────────────

describe('Controlo de acesso por perfil', function () {

    test('utilizador com perfil "user" recebe 403 ao aceder à listagem', function () {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('sgm.index'))
            ->assertStatus(403);
    });

    test('utilizador com perfil "coord" recebe 403 ao aceder à listagem', function () {
        $user = User::factory()->create(['role' => 'coord']);

        $this->actingAs($user)
            ->get(route('sgm.index'))
            ->assertStatus(403);
    });

    test('administrador consegue aceder à listagem', function () {
        $this->get(route('sgm.index'))
            ->assertStatus(200);
    });
});

// ── CRUD principal ────────────────────────────────────────────────────────────

describe('FichaSGMController — Listagem', function () {

    test('pode aceder à listagem de fichas SGM', function () {
        $this->get(route('sgm.index'))
            ->assertStatus(200)
            ->assertViewIs('ficha.listSGM')
            ->assertViewHas('fichas');
    });

    test('listagem filtra por nome do candidato', function () {
        $ficha1 = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Ana Buscável',
        ]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha1->idt_ficha]);

        $ficha2 = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'nom_candidato' => 'Carlos Outro',
        ]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha2->idt_ficha]);

        $response = $this->get(route('sgm.index', ['search' => 'Buscável']));
        $response->assertStatus(200);

        // Verifica que a ficha filtrada está na coleção retornada à view
        $fichas = $response->viewData('fichas');
        expect($fichas->pluck('nom_candidato')->toArray())->toContain('Ana Buscável');
        expect($fichas->pluck('nom_candidato')->toArray())->not->toContain('Carlos Outro');
    });

    test('listagem filtra por evento', function () {
        $evento2 = Evento::factory()->create(['idt_movimento' => TipoMovimento::SGM]);

        $ficha1 = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha1->idt_ficha]);

        $ficha2 = Ficha::factory()->create(['idt_evento' => $evento2->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha2->idt_ficha]);

        $response = $this->get(route('sgm.index', ['evento' => $this->evento->idt_evento]));
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
        FichaSGM::factory()->create(['idt_ficha' => $fichaNova->idt_ficha]);

        $fichaAprovada = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'tip_situacao' => TipoSituacao::APROVADA,
        ]);
        FichaSGM::factory()->create(['idt_ficha' => $fichaAprovada->idt_ficha]);

        $response = $this->get(route('sgm.index', ['situacao' => 'A']));
        $response->assertStatus(200);
        $fichas = $response->viewData('fichas');

        expect($fichas->pluck('idt_ficha'))->toContain($fichaAprovada->idt_ficha);
        expect($fichas->pluck('idt_ficha'))->not->toContain($fichaNova->idt_ficha);
    });
});

describe('FichaSGMController — Criação', function () {

    test('pode aceder ao formulário de criação', function () {
        $this->get(route('sgm.create'))
            ->assertStatus(200)
            ->assertViewIs('ficha.formSGM');
    });

    test('pode criar ficha SGM com dados mínimos obrigatórios', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ficha', [
            'nom_candidato' => 'João Segue-Me',
            'idt_evento' => $this->evento->idt_evento,
        ]);

        $ficha = Ficha::where('nom_candidato', 'João Segue-Me')->first();

        $this->assertDatabaseHas('ficha_sgm', [
            'idt_ficha' => $ficha->idt_ficha,
            'des_naturalidade' => 'Brasília - DF',
            'nom_mae' => 'Maria Mãe',
        ]);
    });

    test('pode criar ficha SGM apenas com dados do pai (sem mãe)', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'pai.only@email.com']),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'nom_mae' => null,
                'tel_mae' => null,
                'eml_mae' => null,
                'nom_pai' => 'José Pai',
                'tel_pai' => '(61) 97777-0001',
            ])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'pai.only@email.com')->first();

        $this->assertDatabaseHas('ficha_sgm', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_pai' => 'José Pai',
        ]);
    });

    test('pode criar ficha SGM com restrições de saúde', function () {
        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'eml_candidato' => 'restricao@email.com',
                'ind_restricao' => 1,
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel),
            [
                'restricoes' => [$this->restricoes[0]->idt_restricao => true],
                'complementos' => [$this->restricoes[0]->idt_restricao => 'Alergia a amendoim'],
            ]
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'restricao@email.com')->first();

        expect($ficha->fichaSaude)->toHaveCount(1);

        $this->assertDatabaseHas('ficha_saude', [
            'idt_ficha' => $ficha->idt_ficha,
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Alergia a amendoim',
        ]);
    });

    test('pode criar ficha SGM com campos opcionais de religião preenchidos', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'religiao@email.com']),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'tip_religiao' => 'E',
                'nom_paroquia' => null,
                'ind_batismo' => 0,
                'ind_eucaristia' => 0,
                'ind_crisma' => 0,
                'des_participa_movimento' => 'Grupo de Jovens',
            ])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'religiao@email.com')->first();

        $this->assertDatabaseHas('ficha_sgm', [
            'idt_ficha' => $ficha->idt_ficha,
            'tip_religiao' => 'E',
            'des_participa_movimento' => 'Grupo de Jovens',
        ]);
    });

    test('pode criar ficha SGM com dados de quem convidou', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'convidou@email.com']),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'nom_convidou' => 'Pedro Amigo',
                'tel_convidou' => '(61) 96666-0001',
                'end_convidou' => 'Rua das Flores, 10',
            ])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHas('success');

        $ficha = Ficha::where('eml_candidato', 'convidou@email.com')->first();

        $this->assertDatabaseHas('ficha_sgm', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_convidou' => 'Pedro Amigo',
            'end_convidou' => 'Rua das Flores, 10',
        ]);
    });
});

describe('FichaSGMController — Validação', function () {

    test('falha ao criar ficha SGM sem nome do candidato', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'nom_candidato' => '']),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('nom_candidato');
    });

    test('falha ao criar ficha SGM sem naturalidade', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['des_naturalidade' => ''])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('des_naturalidade');
    });

    test('falha ao criar ficha SGM sem responsável de emergência', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['idt_falar_com' => ''])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('idt_falar_com');
    });

    test('falha ao criar ficha SGM sem escolaridade', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['tip_escolaridade' => ''])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('tip_escolaridade');
    });

    test('falha ao criar ficha SGM sem situação de escolaridade', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['tip_escolaridade_situacao' => ''])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('tip_escolaridade_situacao');
    });

    test('falha ao criar ficha SGM sem religião', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['tip_religiao' => ''])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('tip_religiao');
    });

    test('falha ao criar ficha SGM sem nenhum dos pais informado', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'nom_mae' => null,
                'nom_pai' => null,
            ])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('filiacao');
    });

    test('falha ao criar ficha SGM com e-mail da mãe inválido', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['eml_mae' => 'nao-e-email'])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('eml_mae');
    });

    test('falha ao criar ficha SGM com e-mail do pai inválido', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'nom_pai' => 'Pai Teste',
                'eml_pai' => 'invalido',
            ])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('eml_pai');
    });

    test('falha ao criar ficha SGM com escolaridade inválida', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['tip_escolaridade' => 'X'])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('tip_escolaridade');
    });

    test('falha ao criar ficha SGM com religião inválida', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['tip_religiao' => 'Z'])
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('tip_religiao');
    });

    test('falha ao criar ficha SGM sem consentimento', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'ind_consentimento' => 0]),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->post(route('sgm.store'), $payload)
            ->assertSessionHasErrors('ind_consentimento');
    });
});

describe('FichaSGMController — Visualização e Edição', function () {

    test('pode visualizar ficha SGM existente', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('sgm.show', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formSGM');
    });

    test('retorna 404 ao tentar visualizar ficha SGM inexistente', function () {
        $this->get(route('sgm.show', 99999))
            ->assertStatus(404);
    });

    test('pode aceder ao formulário de edição', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->get(route('sgm.edit', $ficha->idt_ficha))
            ->assertStatus(200)
            ->assertViewIs('ficha.formSGM');
    });
});

describe('FichaSGMController — Atualização', function () {

    test('pode atualizar ficha SGM com dados válidos', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'atualizado@email.com',
                'tip_genero' => 'F',
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel, [
                'des_naturalidade' => 'São Paulo - SP',
                'nom_mae' => 'Mãe Atualizada',
            ])
        );

        $this->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('sgm.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('ficha', [
            'idt_ficha' => $ficha->idt_ficha,
            'nom_candidato' => 'Nome Atualizado',
        ]);

        $this->assertDatabaseHas('ficha_sgm', [
            'idt_ficha' => $ficha->idt_ficha,
            'des_naturalidade' => 'São Paulo - SP',
            'nom_mae' => 'Mãe Atualizada',
        ]);
    });

    test('atualização recria restrições de saúde', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => true,
        ]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        // Cria restrição inicial
        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
            'txt_complemento' => 'Restrição antiga',
        ]);

        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'eml_candidato' => $ficha->eml_candidato,
                'ind_restricao' => 1,
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel),
            [
                'restricoes' => [$this->restricoes[1]->idt_restricao => true],
                'complementos' => [$this->restricoes[1]->idt_restricao => 'Nova restrição'],
            ]
        );

        $this->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        // Restrição antiga deve ter sido removida
        $this->assertDatabaseMissing('ficha_saude', [
            'idt_ficha' => $ficha->idt_ficha,
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
        ]);

        // Nova restrição deve existir
        $this->assertDatabaseHas('ficha_saude', [
            'idt_ficha' => $ficha->idt_ficha,
            'idt_restricao' => $this->restricoes[1]->idt_restricao,
            'txt_complemento' => 'Nova restrição',
        ]);
    });

    test('atualização sem restrições limpa ficha_saude', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->evento->idt_evento,
            'ind_restricao' => true,
        ]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $ficha->fichaSaude()->create([
            'idt_restricao' => $this->restricoes[0]->idt_restricao,
        ]);

        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'eml_candidato' => $ficha->eml_candidato,
                'ind_restricao' => 0,
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertSessionHas('success');

        expect($ficha->fresh()->fichaSaude)->toHaveCount(0);
    });

    test('falha ao atualizar ficha SGM sem naturalidade', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento]),
            dadosSGMBase($this->responsavel->idt_responsavel, ['des_naturalidade' => ''])
        );

        $this->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertSessionHasErrors('des_naturalidade');
    });
});

// Nota: o método approve() ainda não está implementado no FichaSGMController.
// Adicionar testes de aprovação após implementar o método (ver FichaVemController::approve como referência).

describe('FichaSGMController — Exclusão', function () {

    test('pode excluir ficha SGM (soft delete)', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $this->delete(route('sgm.destroy', $ficha->idt_ficha))
            ->assertRedirect(route('sgm.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('ficha', ['idt_ficha' => $ficha->idt_ficha]);
    });

    test('retorna erro ao tentar excluir ficha SGM inexistente', function () {
        $this->delete(route('sgm.destroy', 99999))
            ->assertStatus(500);
    });
});

describe('FichaSGMController — Redirecionamento Inteligente', function () {
    test('redireciona para a home ao salvar vindo de /sgm', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'publico@email.com']),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->from(route('home.ficha.sgm'))
            ->post(route('sgm.store'), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para sgm.index ao salvar vindo de /fichas/sgm', function () {
        $payload = array_merge(
            dadosFichaBase(['idt_evento' => $this->evento->idt_evento, 'eml_candidato' => 'admin@email.com']),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->from(route('sgm.create'))
            ->post(route('sgm.store'), $payload)
            ->assertRedirect(route('sgm.index'))
            ->assertSessionHas('success');
    });

    test('redireciona para a home ao atualizar vindo de /sgm', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'nom_candidato' => 'Nome Atualizado',
                'eml_candidato' => 'atualizado@email.com',
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->from(route('home.ficha.sgm'))
            ->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');
    });

    test('redireciona para sgm.index ao atualizar vindo de /fichas/sgm', function () {
        $ficha = Ficha::factory()->create(['idt_evento' => $this->evento->idt_evento]);
        FichaSGM::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        $payload = array_merge(
            dadosFichaBase([
                'idt_evento' => $this->evento->idt_evento,
                'nom_candidato' => 'Nome Admin',
                'eml_candidato' => 'admin.atualizado@email.com',
            ]),
            dadosSGMBase($this->responsavel->idt_responsavel)
        );

        $this->from(route('sgm.edit', $ficha->idt_ficha))
            ->put(route('sgm.update', $ficha->idt_ficha), $payload)
            ->assertRedirect(route('sgm.index'))
            ->assertSessionHas('success');
    });
});
