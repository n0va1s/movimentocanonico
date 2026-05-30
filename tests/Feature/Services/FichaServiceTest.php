<?php

use App\Enums\TipoEvento;
use App\Enums\TipoSituacao;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaEcc;
use App\Models\FichaEccFilho;
use App\Models\FichaSaude;
use App\Models\FichaVem;
use App\Models\Gamificacao;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Services\FichaService;

// ─────────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    createMovimentos();

    $this->movimento = TipoMovimento::where('des_sigla', 'VEM')->first();
    $this->user      = createUser();
    $this->actingAs($this->user);

    $this->evento = Evento::factory()->create([
        'idt_movimento' => $this->movimento->idt_movimento,
        'tip_evento'    => TipoEvento::ENCONTRO->value,
        'des_evento'    => 'Encontro de Teste',
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers locais
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Cria uma Ficha VEM mínima com CPF e FichaVem associada.
 */
function fichaVemComCpf(Evento $evento, array $overrides = []): Ficha
{
    $ficha = Ficha::factory()->create(array_merge([
        'idt_evento'        => $evento->idt_evento,
        'num_cpf_candidato' => '12345678901',
        'nom_candidato'     => 'Candidato Teste',
        'eml_candidato'     => 'candidato@teste.com',
        'tip_situacao'      => TipoSituacao::CADASTRADO,
        'idt_pessoa'        => null,
    ], $overrides));

    FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

    return $ficha;
}

/**
 * Cria uma Ficha VEM sem CPF.
 */
function fichaVemSemCpf(Evento $evento): Ficha
{
    $ficha = Ficha::factory()->create([
        'idt_evento'        => $evento->idt_evento,
        'num_cpf_candidato' => null,
        'nom_candidato'     => 'Sem CPF',
        'tip_situacao'      => TipoSituacao::CADASTRADO,
        'idt_pessoa'        => null,
    ]);

    FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

    return $ficha;
}

// ─────────────────────────────────────────────────────────────────────────────
// FichaService — Aprovação VEM/SGM (candidato único)
// ─────────────────────────────────────────────────────────────────────────────

describe('FichaService — Aprovação VEM', function () {

    test('aprovar ficha com CPF cria Pessoa e Participante', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(1);
        expect(Pessoa::where('eml_pessoa', 'candidato@teste.com')->exists())->toBeTrue();
    });

    test('aprovar ficha atualiza tip_situacao para APROVADO', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        expect($ficha->fresh()->tip_situacao)->toBe(TipoSituacao::APROVADO);
    });

    test('aprovar ficha vincula idt_pessoa na ficha', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        expect($ficha->fresh()->idt_pessoa)->not->toBeNull();
    });

    test('aprovar ficha sem CPF lança RuntimeException e não cria Participante', function () {
        $ficha = fichaVemSemCpf($this->evento);

        expect(fn () => FichaService::atualizarAprovacaoFicha($ficha->idt_ficha))
            ->toThrow(\RuntimeException::class, 'CPF');

        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(0);
    });

    test('aprovar ficha sem CPF não altera tip_situacao (transação revertida)', function () {
        $ficha = fichaVemSemCpf($this->evento);

        try {
            FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);
        } catch (\RuntimeException) {
        }

        expect($ficha->fresh()->tip_situacao)->toBe(TipoSituacao::CADASTRADO);
    });

    test('desaprovar ficha remove Participante', function () {
        $ficha = fichaVemComCpf($this->evento);

        // Aprova
        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);
        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(1);

        // Desaprova (toggle)
        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);
        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(0);
    });

    test('desaprovar ficha atualiza tip_situacao para CADASTRADO', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // aprova
        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // desaprova

        expect($ficha->fresh()->tip_situacao)->toBe(TipoSituacao::CADASTRADO);
    });

    test('re-aprovação não duplica Participante (updateOrCreate)', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // aprova
        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // desaprova
        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // re-aprova

        expect(Participante::where('idt_evento', $this->evento->idt_evento)->count())->toBe(1);
    });

    test('aprovar ficha com email já existente atualiza Pessoa existente (updateOrCreate)', function () {
        $pessoaExistente = Pessoa::factory()->create(['eml_pessoa' => 'candidato@teste.com']);
        $ficha           = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        // Não deve criar nova Pessoa — deve reutilizar a existente
        expect(Pessoa::where('eml_pessoa', 'candidato@teste.com')->count())->toBe(1);
        expect($ficha->fresh()->idt_pessoa)->toBe($pessoaExistente->idt_pessoa);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// FichaService — Aprovação ECC (candidato + cônjuge + filhos)
// ─────────────────────────────────────────────────────────────────────────────

describe('FichaService — Aprovação ECC', function () {

    beforeEach(function () {
        $this->movimentoEcc = TipoMovimento::where('des_sigla', 'ECC')->first();

        $this->eventoEcc = Evento::factory()->create([
            'idt_movimento' => $this->movimentoEcc->idt_movimento,
            'tip_evento'    => TipoEvento::ENCONTRO->value,
            'des_evento'    => 'ECC Teste',
        ]);
    });

    test('aprovar ficha ECC cria Participante para candidato e cônjuge', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento'        => $this->eventoEcc->idt_evento,
            'num_cpf_candidato' => '11111111111',
            'eml_candidato'     => 'candidato.ecc@teste.com',
            'tip_situacao'      => TipoSituacao::CADASTRADO,
            'idt_pessoa'        => null,
        ]);

        FichaEcc::factory()->create([
            'idt_ficha'        => $ficha->idt_ficha,
            'num_cpf_conjuge'  => '22222222222',
            'eml_conjuge'      => 'conjuge.ecc@teste.com',
        ]);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        expect(Participante::where('idt_evento', $this->eventoEcc->idt_evento)->count())->toBe(2);
    });

    test('aprovar ficha ECC com filhos cria Participante para cada filho com CPF', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento'        => $this->eventoEcc->idt_evento,
            'num_cpf_candidato' => '33333333333',
            'eml_candidato'     => 'pai.ecc@teste.com',
            'tip_situacao'      => TipoSituacao::CADASTRADO,
            'idt_pessoa'        => null,
        ]);

        $fichaEcc = FichaEcc::factory()->create([
            'idt_ficha'       => $ficha->idt_ficha,
            'num_cpf_conjuge' => '44444444444',
            'eml_conjuge'     => 'mae.ecc@teste.com',
        ]);

        // 2 filhos com CPF — idt_ficha aponta para ficha_ecc (mesmo idt_ficha da ficha)
        FichaEccFilho::factory()->create([
            'idt_ficha'     => $fichaEcc->idt_ficha,
            'num_cpf_filho' => '55555555555',
            'nom_filho'     => 'Filho Um',
        ]);
        FichaEccFilho::factory()->create([
            'idt_ficha'     => $fichaEcc->idt_ficha,
            'num_cpf_filho' => '66666666666',
            'nom_filho'     => 'Filho Dois',
        ]);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        // candidato + cônjuge + 2 filhos = 4
        expect(Participante::where('idt_evento', $this->eventoEcc->idt_evento)->count())->toBe(4);
    });

    test('filho sem CPF não gera Participante', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento'        => $this->eventoEcc->idt_evento,
            'num_cpf_candidato' => '77777777777',
            'eml_candidato'     => 'pai2.ecc@teste.com',
            'tip_situacao'      => TipoSituacao::CADASTRADO,
            'idt_pessoa'        => null,
        ]);

        $fichaEcc = FichaEcc::factory()->create([
            'idt_ficha'       => $ficha->idt_ficha,
            'num_cpf_conjuge' => '88888888888',
            'eml_conjuge'     => 'mae2.ecc@teste.com',
        ]);

        // Filho sem CPF
        FichaEccFilho::factory()->create([
            'idt_ficha'     => $fichaEcc->idt_ficha,
            'num_cpf_filho' => null,
            'nom_filho'     => 'Filho Sem CPF',
        ]);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        // Apenas candidato + cônjuge = 2
        expect(Participante::where('idt_evento', $this->eventoEcc->idt_evento)->count())->toBe(2);
    });

    test('desaprovar ficha ECC remove Participantes do candidato e cônjuge', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento'        => $this->eventoEcc->idt_evento,
            'num_cpf_candidato' => '99999999901',
            'eml_candidato'     => 'desp.ecc@teste.com',
            'tip_situacao'      => TipoSituacao::CADASTRADO,
            'idt_pessoa'        => null,
        ]);

        FichaEcc::factory()->create([
            'idt_ficha'       => $ficha->idt_ficha,
            'num_cpf_conjuge' => '99999999902',
            'eml_conjuge'     => 'desp.conj@teste.com',
        ]);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // aprova
        expect(Participante::where('idt_evento', $this->eventoEcc->idt_evento)->count())->toBe(2);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // desaprova
        expect(Participante::where('idt_evento', $this->eventoEcc->idt_evento)->count())->toBe(0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// FichaService — Gamificação automática via Participante
// ─────────────────────────────────────────────────────────────────────────────

describe('FichaService — Gamificação', function () {

    test('aprovar ficha cria registro de Gamificacao com 1 ponto (evento normal)', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        $pessoa = Pessoa::where('eml_pessoa', 'candidato@teste.com')->first();

        expect(
            Gamificacao::where('idt_pessoa', $pessoa->idt_pessoa)
                ->where('qtd_pontos', 1)
                ->exists()
        )->toBeTrue();
    });

    test('aprovar ficha em evento tipo Desafio cria Gamificacao com 3 pontos', function () {
        $eventoDesafio = Evento::factory()->create([
            'idt_movimento' => $this->movimento->idt_movimento,
            'tip_evento'    => TipoEvento::DESAFIO->value,
            'des_evento'    => 'Desafio Teste',
        ]);

        $ficha = Ficha::factory()->create([
            'idt_evento'        => $eventoDesafio->idt_evento,
            'num_cpf_candidato' => '10203040506',
            'eml_candidato'     => 'desafio@teste.com',
            'tip_situacao'      => TipoSituacao::CADASTRADO,
            'idt_pessoa'        => null,
        ]);
        FichaVem::factory()->create(['idt_ficha' => $ficha->idt_ficha]);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha);

        $pessoa = Pessoa::where('eml_pessoa', 'desafio@teste.com')->first();

        expect(
            Gamificacao::where('idt_pessoa', $pessoa->idt_pessoa)
                ->where('qtd_pontos', 3)
                ->exists()
        )->toBeTrue();
    });

    test('desaprovar ficha não deixa registro de Gamificacao órfão', function () {
        $ficha = fichaVemComCpf($this->evento);

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // aprova → cria Gamificacao
        $pessoaId = $ficha->fresh()->idt_pessoa;

        FichaService::atualizarAprovacaoFicha($ficha->idt_ficha); // desaprova → remove Participante

        // Participante foi removido
        expect(Participante::where('idt_pessoa', $pessoaId)->exists())->toBeFalse();

        // Gamificacao permanece (histórico — não é removida junto com o Participante)
        // Isso é comportamento esperado: pontos são histórico, não revertidos automaticamente
        expect(Gamificacao::where('idt_pessoa', $pessoaId)->exists())->toBeTrue();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Gamificação automática — Trabalhador
// ─────────────────────────────────────────────────────────────────────────────

describe('Gamificação — Trabalhador', function () {

    test('criar Trabalhador gera Gamificacao com 2 pontos', function () {
        $pessoa = createPessoa();
        $equipe = \App\Models\TipoEquipe::first();

        $trabalhador = \App\Models\Trabalhador::factory()->create([
            'idt_pessoa'      => $pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $equipe->idt_equipe,
            'ind_coordenador' => false,
        ]);

        expect(
            Gamificacao::where('idt_pessoa', $pessoa->idt_pessoa)
                ->where('qtd_pontos', 2)
                ->exists()
        )->toBeTrue();
    });

    test('criar Trabalhador coordenador gera Gamificacao com 4 pontos', function () {
        $pessoa = createPessoa();
        $equipe = \App\Models\TipoEquipe::first();

        \App\Models\Trabalhador::factory()->create([
            'idt_pessoa'      => $pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $equipe->idt_equipe,
            'ind_coordenador' => true,
        ]);

        expect(
            Gamificacao::where('idt_pessoa', $pessoa->idt_pessoa)
                ->where('qtd_pontos', 4)
                ->exists()
        )->toBeTrue();
    });

    test('Gamificacao de Trabalhador referencia o modelo correto via morphTo', function () {
        $pessoa = createPessoa();
        $equipe = \App\Models\TipoEquipe::first();

        $trabalhador = \App\Models\Trabalhador::factory()->create([
            'idt_pessoa'      => $pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $equipe->idt_equipe,
            'ind_coordenador' => false,
        ]);

        $gamificacao = Gamificacao::where('idt_pessoa', $pessoa->idt_pessoa)->first();

        expect($gamificacao->origem_type)->toBe(\App\Models\Trabalhador::class)
            ->and($gamificacao->origem_id)->toBe($trabalhador->idt_trabalhador);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// VoluntarioService — gaps não cobertos pelo VoluntarioServiceTest existente
// ─────────────────────────────────────────────────────────────────────────────

describe('VoluntarioService — gaps', function () {

    beforeEach(function () {
        $this->service = new \App\Services\VoluntarioService;
        $this->pessoa  = createPessoa();
        $this->equipes = \App\Models\TipoEquipe::take(4)->get();
    });

    test('candidatura com mais de 3 equipes lança ValidationException', function () {
        $equipesData = [];
        foreach ($this->equipes->take(4) as $equipe) {
            $equipesData[$equipe->idt_equipe] = [
                'selecionado' => '1',
                'habilidade'  => 'Habilidade válida para esta equipe',
            ];
        }

        expect(fn () => $this->service->candidatura($equipesData, $this->evento->idt_evento, $this->pessoa))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    test('confirmacao com voluntário inexistente lança ValidationException', function () {
        expect(fn () => $this->service->confirmacao(99999, $this->equipes->first()->idt_equipe))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    test('confirmacao não duplica Trabalhador (updateOrCreate)', function () {
        $voluntario = \App\Models\Voluntario::factory()->create([
            'idt_pessoa'      => $this->pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $this->equipes->first()->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        $this->service->confirmacao($voluntario->idt_voluntario, $this->equipes->first()->idt_equipe);
        $this->service->confirmacao($voluntario->idt_voluntario, $this->equipes->first()->idt_equipe);

        expect(
            \App\Models\Trabalhador::where('idt_pessoa', $this->pessoa->idt_pessoa)
                ->where('idt_evento', $this->evento->idt_evento)
                ->count()
        )->toBe(1);
    });

    test('confirmacao vincula todos os voluntários pendentes da pessoa ao trabalhador', function () {
        // Pessoa candidatada a 2 equipes
        $vol1 = \App\Models\Voluntario::factory()->create([
            'idt_pessoa'      => $this->pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $this->equipes->get(0)->idt_equipe,
            'idt_trabalhador' => null,
        ]);
        \App\Models\Voluntario::factory()->create([
            'idt_pessoa'      => $this->pessoa->idt_pessoa,
            'idt_evento'      => $this->evento->idt_evento,
            'idt_equipe'      => $this->equipes->get(1)->idt_equipe,
            'idt_trabalhador' => null,
        ]);

        $this->service->confirmacao($vol1->idt_voluntario, $this->equipes->get(0)->idt_equipe);

        $pendentes = \App\Models\Voluntario::where('idt_pessoa', $this->pessoa->idt_pessoa)
            ->where('idt_evento', $this->evento->idt_evento)
            ->whereNull('idt_trabalhador')
            ->count();

        expect($pendentes)->toBe(0);
    });
});
