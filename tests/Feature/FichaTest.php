<?php

use App\Enums\TipoSituacao;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaEcc;
use App\Models\FichaVem;
use App\Models\TipoMovimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->user);

    $this->movimentoVem = TipoMovimento::factory()->create(['des_sigla' => 'VEM']);
    $this->movimentoEcc = TipoMovimento::factory()->create(['des_sigla' => 'ECC']);

    $this->eventoVem = Evento::factory()->create([
        'idt_movimento' => $this->movimentoVem->idt_movimento,
    ]);

    $this->eventoEcc = Evento::factory()->create([
        'idt_movimento' => $this->movimentoEcc->idt_movimento,
    ]);
});

describe('Ficha – Comportamentos comuns por movimento', function () {

    test('ficha VEM aprovada altera flag corretamente', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
        ]);

        FichaVem::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->get(route('vem.approve', $ficha->idt_ficha));

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });

    test('ficha ECC aprovada altera flag corretamente', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoEcc->idt_evento,
        ]);

        FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->get(route('ecc.approve', $ficha->idt_ficha));

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::APROVADA);
    });

    test('ficha ECC pode ser desaprovada', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoEcc->idt_evento,
            'tip_situacao' => TipoSituacao::APROVADA,
        ]);

        FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->get(route('ecc.approve', $ficha->idt_ficha));

        $ficha->refresh();
        expect($ficha->tip_situacao)->toBe(TipoSituacao::NOVA);
    });

    test('ficha VEM mantém integridade ao ser excluída', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoVem->idt_evento,
        ]);

        FichaVem::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->delete(route('vem.destroy', $ficha->idt_ficha));

        $this->assertSoftDeleted('ficha', [
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->assertDatabaseHas('ficha_vem', [
            'idt_ficha' => $ficha->idt_ficha,
        ]);
    });

    test('ficha ECC mantém integridade ao ser excluída', function () {
        $ficha = Ficha::factory()->create([
            'idt_evento' => $this->eventoEcc->idt_evento,
        ]);

        FichaEcc::factory()->create([
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->delete(route('ecc.destroy', $ficha->idt_ficha));

        $this->assertSoftDeleted('ficha', [
            'idt_ficha' => $ficha->idt_ficha,
        ]);

        $this->assertDatabaseHas('ficha_ecc', [
            'idt_ficha' => $ficha->idt_ficha,
        ]);
    });

    test('validação de consentimento é comum a todos os movimentos', function () {
        $this->post(route('vem.store'), [
            'ind_consentimento' => false,
        ])->assertSessionHasErrors(['ind_consentimento']);

        $this->post(route('ecc.store'), [
            'ind_consentimento' => false,
        ])->assertSessionHasErrors(['ind_consentimento']);
    });

    test('email inválido falha para VEM e ECC', function () {
        $this->post(route('vem.store'), [
            'eml_candidato' => 'email-invalido',
        ])->assertSessionHasErrors(['eml_candidato']);

        $this->post(route('ecc.store'), [
            'eml_candidato' => 'email-invalido',
        ])->assertSessionHasErrors(['eml_candidato']);
    });
});
