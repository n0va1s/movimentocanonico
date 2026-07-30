<?php

use App\Models\User;
use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('trabalhador consegue registrar aceite do termo de visitacao especifico na tabela trabalhador', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $pessoa = $user->pessoa;
    $evento1 = Evento::factory()->create();
    $evento2 = Evento::factory()->create();
    $equipeVisitacao = TipoEquipe::factory()->create(['des_grupo' => 'Visitação']);

    $trabalhador1 = Trabalhador::create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento1->idt_evento,
        'idt_equipe' => $equipeVisitacao->idt_equipe,
        'ind_termo_lgpd_aceito' => false,
    ]);

    $trabalhador2 = Trabalhador::create([
        'idt_pessoa' => $pessoa->idt_pessoa,
        'idt_evento' => $evento2->idt_evento,
        'idt_equipe' => $equipeVisitacao->idt_equipe,
        'ind_termo_lgpd_aceito' => false,
    ]);

    expect($user->hasAceitouTermoVisitacao($evento1->idt_evento))->toBeFalse();

    $user->registrarAceiteTermoVisitacao($evento1->idt_evento, '127.0.0.1');

    $trabalhador1->refresh();
    $trabalhador2->refresh();

    expect($trabalhador1->ind_termo_lgpd_aceito)->toBeTrue();
    expect($trabalhador1->des_ip_termo_lgpd)->toBe('127.0.0.1');
    expect($trabalhador1->dat_termo_lgpd_aceito)->not()->toBeNull();

    expect($trabalhador2->ind_termo_lgpd_aceito)->toBeFalse();
    expect($user->hasAceitouTermoVisitacao($evento1->idt_evento))->toBeTrue();
    expect($user->hasAceitouTermoVisitacao($evento2->idt_evento))->toBeFalse();
});

test('admin e dirigente sao isentos da verificacao do termo de visitacao', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $dirig = User::factory()->create(['role' => User::ROLE_DIRIG]);

    expect($admin->hasAceitouTermoVisitacao(1))->toBeTrue();
    expect($dirig->hasAceitouTermoVisitacao(1))->toBeTrue();
});
