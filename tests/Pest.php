<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Cleanup após cada teste (opcional)
afterEach(function () {
    // Limpa cache se necessário
    Cache::flush();
});

function createUser(): User
{
    $user = User::factory()->create();
    Pessoa::factory()->for($user, 'usuario')->create();

    return $user;
}

function createMovimentos(): void
{
    if (!TipoMovimento::where('idt_movimento', 1)->exists()) {
        TipoMovimento::forceCreate([
            'idt_movimento' => 1,
            'des_sigla' => 'ECC',
            'nom_movimento' => 'Encontro de Casais com Cristo',
            'dat_inicio' => '1980-01-01',
        ]);
    }

    if (!TipoMovimento::where('idt_movimento', 2)->exists()) {
        TipoMovimento::forceCreate([
            'idt_movimento' => 2,
            'des_sigla' => 'VEM',
            'nom_movimento' => 'Encontro de Adolescentes com Cristo',
            'dat_inicio' => '2000-07-01',
        ]);
    }

    if (!TipoMovimento::where('idt_movimento', 3)->exists()) {
        TipoMovimento::forceCreate([
            'idt_movimento' => 3,
            'des_sigla' => 'Segue-Me',
            'nom_movimento' => 'Encontro de Jovens com Cristo',
            'dat_inicio' => '1990-12-31',
        ]);
    }

    $tipoMovimento = TipoMovimento::all()->first()->idt_movimento;

    $equipes = [
        ['des_grupo' => 'Alimentação', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Bandinha', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Coordenação Geral', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Emaús', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Limpeza', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Oração', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Recepção', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Reportagem', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Sala', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Secretaria', 'idt_movimento' => $tipoMovimento],
        ['des_grupo' => 'Vendinha', 'idt_movimento' => $tipoMovimento],
    ];

    DB::table('tipo_equipe')->insertOrIgnore($equipes);
}

function createEvento(): Evento
{
    return Evento::factory()->create([
        'idt_movimento' => TipoMovimento::all()->first()->idt_movimento,
    ]);
}

function createPessoa(): Pessoa
{
    return Pessoa::factory()->create([
        'idt_parceiro' => null,
    ]);
}
