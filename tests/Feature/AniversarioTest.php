<?php

use App\Mail\AniversarioMail;
use App\Models\Pessoa;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);
uses(InteractsWithConsole::class);

describe('Comando de console para aniversários (mov:enviar-emails-aniversario)', function () {
    beforeEach(function () {
        // Usa Event::fake() para evitar que listeners (como email de Boas-Vindas) sejam disparados.
        Event::fake();

        // Configura o sistema de email para que não envie emails reais.
        Mail::fake();
    });

    test('envia email apenas a utilizadores com aniversário hoje', function () {

        // 1. Configuração de Timezone (Importante para testes de data)
        Carbon::setTestNow(now());
        $hoje = now()->format('m-d');

        // Criacao da massa
        $userBirthday = Pessoa::factory()->create([
            'dat_nascimento' => now()->subYears(30)->format("Y-$hoje"),
            'eml_pessoa' => 'aniversariante@example.com',
        ]);
        $userNoBirthday = Pessoa::factory()->create([
            'dat_nascimento' => now()->subDay()->subYears(25)->format('Y-m-d'),
            'eml_pessoa' => 'ontem@example.com',
        ]);
        $userNoBirthday2 = Pessoa::factory()->create([
            'dat_nascimento' => now()->addDay()->subYears(40)->format('Y-m-d'),
            'eml_pessoa' => 'amanha@example.com',
        ]);

        $exitCode = Artisan::call('mov:enviar-emails-aniversario');

        // Assegurar que foi enviado APENAS 1 email.
        Mail::assertSent(AniversarioMail::class, 1);

        // Assegurar que o email foi enviado para o utilizador correto.
        Mail::assertSent(AniversarioMail::class, function ($mail) use ($userBirthday) {
            return $mail->hasTo($userBirthday->eml_pessoa);
        });

        // Assegurar que NENHUM email foi enviado para os utilizadores não elegíveis.
        Mail::assertNotSent(AniversarioMail::class, function ($mail) use ($userNoBirthday, $userNoBirthday2) {
            return $mail->hasTo($userNoBirthday->eml_pessoa) || $mail->hasTo($userNoBirthday2->eml_pessoa);
        });

        // Assegurar que o comando terminou sem erros (exit code 0).
        $this->assertEquals(0, $exitCode);
    });

    test('não envia emails quando não há aniversários hoje', function () {

        Carbon::setTestNow(now());

        // Criar utilizadores sem aniversário hoje (aniversário amanhã)
        $amanha = now()->addDay()->format('Y-m-d');
        Pessoa::factory()->count(5)->create([
            'dat_nascimento' => $amanha,
        ]);

        $exitCode = Artisan::call('mov:enviar-emails-aniversario');

        Mail::assertNothingSent();

        $this->assertEquals(0, $exitCode);
    });
});
