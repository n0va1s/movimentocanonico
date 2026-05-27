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
// Públicas
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
    } catch (\Exception $e) {
        return 'Erro ao criar o link: ' . $e->getMessage();
    }
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/', [HomeController::class, 'contato'])->name('home.contato');

// ---------------------------------------------------------------------------
// Área autenticada — todos os perfis
// ---------------------------------------------------------------------------

Route::middleware(['auth'])->group(function () {

    // Todos autenticados
    Route::get('/vem', [HomeController::class, 'fichaVem'])->name('home.ficha.vem');
    Route::get('/ecc', [HomeController::class, 'fichaEcc'])->name('home.ficha.ecc');
    Route::get('/sgm', [HomeController::class, 'fichaSgm'])->name('home.ficha.sgm');
    
    // Usada no cadastro de fichas e de pessoas
    Route::get('pessoas/{cpf}/busca', [PessoaController::class, 'buscaPorCpf'])->name('pessoas.busca');
    
    Route::get('/timeline', [EventoController::class, 'timeline'])->name('timeline.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/aniversario', [AniversarioController::class, 'index'])->name('aniversario.index');

    Route::get('/termo-sgm', fn () => view('termos.termoSGM'))->name('termo.sgm');
    Route::get('/termo-vem', fn () => view('termos.termoVEM'))->name('termo.vem');

    Route::post('/participantes/{evento}/{pessoa}', [EventoController::class, 'confirm'])->name('participantes.confirm');

    Route::get('/minha-equipe', [TrabalhadorController::class, 'minhaEquipe'])->name('trabalhadores.minha-equipe');

    // Trabalhadores — create/store/review/destroy acessíveis a todos autenticados
    Route::get('/trabalhadores/create', [TrabalhadorController::class, 'create'])->name('trabalhadores.create');
    Route::post('/trabalhadores', [TrabalhadorController::class, 'store'])->name('trabalhadores.store');
    Route::get('/trabalhadores/review', [TrabalhadorController::class, 'review'])->name('trabalhadores.review');
    Route::delete('/trabalhadores/{id}', [TrabalhadorController::class, 'destroy'])->name('trabalhadores.destroy');

    Route::get('/avaliacao', [TrabalhadorController::class, 'review'])->name('avaliacao.review');
    Route::post('/avaliacao', [TrabalhadorController::class, 'send'])->name('avaliacao.send');

    Route::get('/quadrante', [TrabalhadorController::class, 'generate'])->name('quadrante.list');

    Route::get('/montagem', [TrabalhadorController::class, 'mount'])->name('montagem.list');
    
    /*
    Route::get('/participantes', [ParticipanteController::class, 'index'])->name('participantes.index');
    Route::post('/participantes', [ParticipanteController::class, 'change'])->name('participantes.change');
    */
    
    // Eventos - listagem
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');

    // Meus dados (Comentados pois entram em conflito com Route::resources e quebram a rota pessoas.create)
    // Route::get('/pessoas/{pessoa}/edit', [PessoaController::class, 'edit'])->name('pessoas.edit');
    // Route::put('/pessoas/{pessoa}', [PessoaController::class, 'update'])->name('pessoas.update');
    // Route::patch('/pessoas/{pessoa}', [PessoaController::class, 'update']);
    // Route::get('/pessoas/{pessoa}', [PessoaController::class, 'show'])->name('pessoas.show');

    // Configurações pessoais
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    // -----------------------------------------------------------------------
    // Admin + Coordenador (substitui middleware "manager")
    // -----------------------------------------------------------------------
/*
    Route::middleware(['can:gerenciar-trabalhadores'])->group(function () {
        Route::get('/trabalhadores', [TrabalhadorController::class, 'index'])->name('trabalhadores.index');
    });

    Route::middleware(['can:confirmar-montagem'])->group(function () {
        Route::post('/montagem', [TrabalhadorController::class, 'confirm'])->name('montagem.confirm');
    });
*/

    // -----------------------------------------------------------------------
    // Gerenciamento do evento
    // Admin - todos os eventos e todas as abas
    // espec - somente se estiver trabalhando e algumas abas
    // coord - somente se estiver trabalhando e todas as abas
    // -----------------------------------------------------------------------
    Volt::route('eventos/{evento}/gerenciamento', 'evento.gerenciamento')
        ->middleware('can:gerenciar,evento')
        ->name('eventos.gerenciamento');

    // -----------------------------------------------------------------------
    // Aprovação de fichas
    // Admin pode aprovar qualquer ficha de qualquer evento
    // Espec pode aprovar fichas do evento que estiver trabalhando
    // -----------------------------------------------------------------------

    Route::get('fichas/vem/{id}/approve', [FichaVemController::class, 'approve'])->name('vem.approve');
    Route::get('fichas/ecc/{id}/approve', [FichaEccController::class, 'approve'])->name('ecc.approve');
    Route::get('fichas/sgm/{id}/approve', [FichaSGMController::class, 'approve'])->name('sgm.approve');

    // -----------------------------------------------------------------------
    // Somente admin
    // -----------------------------------------------------------------------

    // Configurações
    Route::middleware(['can:acessar-configuracoes'])->group(function () {
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

    // Contatos
    Route::middleware(['can:acessar-contatos'])->group(function () {
        Route::get('/contatos', [ContatoController::class, 'index'])->name('contatos.index');
        Route::delete('/contatos/{id}', [ContatoController::class, 'destroy'])->name('contatos.destroy');
    });

    // -----------------------------------------------------------------------
    // CRUD protegido por policies nos controllers
    // -----------------------------------------------------------------------

    Route::resources([
        'eventos' => EventoController::class,
        'pessoas' => PessoaController::class,
        'fichas/vem' => FichaVemController::class,
        'fichas/ecc' => FichaEccController::class,
        'fichas/sgm' => FichaSGMController::class,
    ]);
});

require __DIR__.'/auth.php';