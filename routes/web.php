<?php

use App\Http\Controllers\AniversarioController;
use App\Http\Controllers\ConfiguracoesController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\FichaEccController;
use App\Http\Controllers\FichaSGMController;
use App\Http\Controllers\FichaVemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParticipanteController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\TipoEquipeController;
use App\Http\Controllers\TipoMovimentoController;
use App\Http\Controllers\TipoPerfilController;
use App\Http\Controllers\TipoResponsavelController;
use App\Http\Controllers\TipoRestricaoController;
use App\Http\Controllers\TrabalhadorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ---------------------------------------------------------------------------
// Utilitários (sem auth)
// ---------------------------------------------------------------------------

Route::get('/limpar-tudo', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');

    return 'Clear realizado! Tente acessar a home agora.';
});

Route::get('/otimizar-tudo', function () {
    Artisan::call('optimize');

    return 'Optimize realizado! Tente acessar a home agora.';
});

Route::get('/storage-link', function () {
    try {
        Artisan::call('storage:link');

        return 'Link simbólico criado com sucesso!';
    } catch (Exception $e) {
        return 'Erro ao criar o link: '.$e->getMessage();
    }
});

// ---------------------------------------------------------------------------
// Públicas
// ---------------------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/', [HomeController::class, 'contato'])->name('home.contato');

// ---------------------------------------------------------------------------
// Área autenticada — todos os perfis
// ---------------------------------------------------------------------------

Route::middleware(['auth'])->group(function () {

    Route::get('/vem', [HomeController::class, 'fichaVem'])->name('home.ficha.vem');
    Route::get('/ecc', [HomeController::class, 'fichaEcc'])->name('home.ficha.ecc');
    Route::get('/sgm', [HomeController::class, 'fichaSgm'])->name('home.ficha.sgm');

    // Submissão de fichas por candidatos (todos perfis autenticados)
    Route::post('/fichas/vem', [FichaVemController::class, 'store'])->name('vem.store');
    Route::post('/fichas/ecc', [FichaEccController::class, 'store'])->name('ecc.store');
    Route::post('/fichas/sgm', [FichaSGMController::class, 'store'])->name('sgm.store');

    Route::redirect('settings', 'settings/profile');

    Route::get('/timeline', [EventoController::class, 'timeline'])->name('timeline.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/aniversario', [AniversarioController::class, 'index'])->name('aniversario.index');

    Route::get('/termo-sgm', fn () => view('termos.termoSGM'))->name('termo.sgm');
    Route::get('/termo-vem', fn () => view('termos.termoVEM'))->name('termo.vem');

    Route::post('/participantes/{evento}/{pessoa}', [EventoController::class, 'confirm'])->name('participantes.confirm');

    // Trabalhadores — create/review/store/destroy acessíveis a todos autenticados
    Route::get('/trabalhadores/create', [TrabalhadorController::class, 'create'])->name('trabalhadores.create');
    Route::post('/trabalhadores', [TrabalhadorController::class, 'store'])->name('trabalhadores.store');
    Route::get('/trabalhadores/review', [TrabalhadorController::class, 'review'])->name('trabalhadores.review');
    Route::delete('/trabalhadores/{id}', [TrabalhadorController::class, 'destroy'])->name('trabalhadores.destroy');
    Route::get('/minha-equipe', [TrabalhadorController::class, 'minhaEquipe'])->name('trabalhadores.minha-equipe');

    // Listagens — todos autenticados (apenas eventos; pessoas e fichas são admin)
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');

    // Edição do próprio perfil de pessoa — todos os perfis autenticados
    Route::get('/pessoas/{pessoa}/edit', [PessoaController::class, 'edit'])->name('pessoas.edit');
    Route::put('/pessoas/{pessoa}', [PessoaController::class, 'update'])->name('pessoas.update');
    Route::patch('/pessoas/{pessoa}', [PessoaController::class, 'update']);

    // Visualização de pessoa — todos os perfis autenticados (ex: coordenadores vendo membros da equipe)
    Route::get('/pessoas/{pessoa}', [PessoaController::class, 'show'])->name('pessoas.show')->where('pessoa', '[0-9]+');

    // Settings — todos autenticados
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // -----------------------------------------------------------------------
    // Admin + Coord
    // -----------------------------------------------------------------------

    Route::middleware(['role:admin,coord'])->group(function () {
        Route::get('/trabalhadores', [TrabalhadorController::class, 'index'])->name('trabalhadores.index');
    });

    // -----------------------------------------------------------------------
    // Gerenciamento de evento: admin + coord + espec
    // -----------------------------------------------------------------------

    Route::middleware(['role:admin,coord,espec'])->group(function () {
        Volt::route('eventos/{evento}/gerenciamento', 'evento.gerenciamento')->name('eventos.gerenciamento');
    });

    // -----------------------------------------------------------------------
    // Somente admin: criar/editar/excluir/visualizar recursos e configurações
    // -----------------------------------------------------------------------

    Route::middleware(['role:admin'])->group(function () {

        // Contatos
        Route::get('/contatos', [ContatoController::class, 'index'])->name('contatos.index');
        Route::delete('/contatos/{id}', [ContatoController::class, 'destroy'])->name('contatos.destroy');

        // Eventos — create deve vir antes de {evento} para não ser capturado pelo show
        Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
        Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');
        Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
        Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
        Route::patch('/eventos/{evento}', [EventoController::class, 'update']);
        Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');

        // Pessoas — listagem, busca e CRUD
        Route::get('/pessoas', [PessoaController::class, 'index'])->name('pessoas.index');
        Route::get('pessoas/{cpf}/busca', [PessoaController::class, 'buscaPorCpf'])->name('pessoas.busca');
        Route::get('/pessoas/create', [PessoaController::class, 'create'])->name('pessoas.create');
        Route::post('/pessoas', [PessoaController::class, 'store'])->name('pessoas.store');
        Route::delete('/pessoas/{pessoa}', [PessoaController::class, 'destroy'])->name('pessoas.destroy');

        // Fichas VEM — listagem, aprovação e CRUD
        Route::get('/fichas/vem', [FichaVemController::class, 'index'])->name('vem.index');
        Route::get('fichas/vem/{id}/approve', [FichaVemController::class, 'approve'])->name('vem.approve');
        Route::get('/fichas/vem/create', [FichaVemController::class, 'create'])->name('vem.create');
        Route::get('/fichas/vem/{vem}', [FichaVemController::class, 'show'])->name('vem.show');
        Route::get('/fichas/vem/{vem}/edit', [FichaVemController::class, 'edit'])->name('vem.edit');
        Route::put('/fichas/vem/{vem}', [FichaVemController::class, 'update'])->name('vem.update');
        Route::patch('/fichas/vem/{vem}', [FichaVemController::class, 'update']);
        Route::delete('/fichas/vem/{vem}', [FichaVemController::class, 'destroy'])->name('vem.destroy');

        // Fichas ECC — listagem, aprovação e CRUD
        Route::get('/fichas/ecc', [FichaEccController::class, 'index'])->name('ecc.index');
        Route::get('fichas/ecc/{id}/approve', [FichaEccController::class, 'approve'])->name('ecc.approve');
        Route::get('/fichas/ecc/create', [FichaEccController::class, 'create'])->name('ecc.create');
        Route::get('/fichas/ecc/{ecc}', [FichaEccController::class, 'show'])->name('ecc.show');
        Route::get('/fichas/ecc/{ecc}/edit', [FichaEccController::class, 'edit'])->name('ecc.edit');
        Route::put('/fichas/ecc/{ecc}', [FichaEccController::class, 'update'])->name('ecc.update');
        Route::patch('/fichas/ecc/{ecc}', [FichaEccController::class, 'update']);
        Route::delete('/fichas/ecc/{ecc}', [FichaEccController::class, 'destroy'])->name('ecc.destroy');

        // Fichas SGM — listagem, aprovação e CRUD
        Route::get('/fichas/sgm', [FichaSGMController::class, 'index'])->name('sgm.index');
        Route::get('fichas/sgm/{id}/approve', [FichaSGMController::class, 'approve'])->name('sgm.approve');
        Route::get('/fichas/sgm/create', [FichaSGMController::class, 'create'])->name('sgm.create');
        Route::get('/fichas/sgm/{sgm}', [FichaSGMController::class, 'show'])->name('sgm.show');
        Route::get('/fichas/sgm/{sgm}/edit', [FichaSGMController::class, 'edit'])->name('sgm.edit');
        Route::put('/fichas/sgm/{sgm}', [FichaSGMController::class, 'update'])->name('sgm.update');
        Route::patch('/fichas/sgm/{sgm}', [FichaSGMController::class, 'update']);
        Route::delete('/fichas/sgm/{sgm}', [FichaSGMController::class, 'destroy'])->name('sgm.destroy');

        // Configurações
        Route::get('/configuracoes', [ConfiguracoesController::class, 'index'])->name('configuracoes.index');
        Route::get('/configuracoes/role', [TipoPerfilController::class, 'index'])->name('role.index');
        Route::post('/configuracoes/role', [TipoPerfilController::class, 'store'])->name('role.store');
        Route::post('/configuracoes/role/change', [TipoPerfilController::class, 'change'])->name('role.change');

        Route::resources([
            'configuracoes/equipe' => TipoEquipeController::class,
            'configuracoes/movimento' => TipoMovimentoController::class,
            'configuracoes/responsavel' => TipoResponsavelController::class,
            'configuracoes/restricao' => TipoRestricaoController::class,
        ]);
    });
});

require __DIR__.'/auth.php';
