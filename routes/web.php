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
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\TipoEquipeController;
use App\Http\Controllers\TipoPerfilController;
use App\Http\Controllers\TrabalhadorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ---------------------------------------------------------------------------
// Utilitários (Somente admin)
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'role:admin'])->group(function () {

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

Route::get('/encerrar-eventos', function () {
    try {
        Artisan::call('mov:deletar-eventos-finalizados');

        return 'Eventos finalizados encerrados com sucesso!';
    } catch (Exception $e) {
        return 'Erro ao encerrar eventos: '.$e->getMessage();
    }
});
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

    Route::get('pessoas/{cpf}/busca', [PessoaController::class, 'buscaPorCpf'])->name('pessoas.busca');

    Route::get('/termo-sgm', fn () => view('termos.termoSGM'))->name('termo.sgm');
    Route::get('/termo-vem', fn () => view('termos.termoVEM'))->name('termo.vem');

    Route::middleware(['role:admin,coord,espec,sales'])->group(function () {
        Route::get('/minha-equipe', [TrabalhadorController::class, 'minhaEquipe'])->name('trabalhadores.minha-equipe');
    });
    Route::get('/trabalhadores/create', [TrabalhadorController::class, 'create'])->name('trabalhadores.create');
    Route::post('/trabalhadores', [TrabalhadorController::class, 'store'])->name('trabalhadores.store');

    // Listagens — todos autenticados (apenas eventos; pessoas e fichas são admin)
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');

    Route::post('/participantes/{evento}/{pessoa}', [EventoController::class, 'confirm'])->name('participantes.confirm');

    Route::get('/pessoas/{pessoa}/edit', [PessoaController::class, 'edit'])->name('pessoas.edit');
    Route::put('/pessoas/{pessoa}', [PessoaController::class, 'update'])->name('pessoas.update');
    Route::patch('/pessoas/{pessoa}', [PessoaController::class, 'update']);
    Route::get('/pessoas/{pessoa}', [PessoaController::class, 'show'])->name('pessoas.show')->where('pessoa', '[0-9]+');

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

    Route::middleware(['role:admin,espec'])->group(function () {
        Volt::route('eventos/{evento}/gerenciamento', 'evento.gerenciamento')
            ->name('eventos.gerenciamento')
            ->withTrashed();
    });

    Route::middleware(['role:admin,coord,espec'])->group(function () {
        // Módulo de Mensagens
        Volt::route('mensagens', 'mensagens.index')->name('mensagens.index');
        Volt::route('mensagens/criar', 'mensagens.create')->name('mensagens.create');
        Volt::route('mensagens/{mensagem}', 'mensagens.show')->name('mensagens.show');
    });

    // -----------------------------------------------------------------------
    // Importação de Planilhas (Definido antes do wildcard /eventos/{evento} para evitar 404)
    // -----------------------------------------------------------------------
    Route::middleware(['role:admin,espec'])->group(function () {
        Route::get('/eventos/importar', [ImportController::class, 'index'])->name('eventos.importar');
        Route::post('/eventos/importar/participantes', [ImportController::class, 'importarParticipantes'])->name('eventos.importar.participantes');
        Route::post('/eventos/importar/trabalhadores', [ImportController::class, 'importarTrabalhadores'])->name('eventos.importar.trabalhadores');
        Route::get('/eventos/importar/modelo-participantes', [ImportController::class, 'downloadModeloParticipantes'])->name('eventos.importar.modelo-participantes');
        Route::get('/eventos/importar/modelo-trabalhadores', [ImportController::class, 'downloadModeloTrabalhadores'])->name('eventos.importar.modelo-trabalhadores');
    });

    // -----------------------------------------------------------------------
    // Somente admin: criar/editar/excluir/visualizar recursos e configurações
    // -----------------------------------------------------------------------

    Route::middleware(['role:admin'])->group(function () {

        // Configurações Globais
        Route::get('/configuracoes', [ConfiguracoesController::class, 'index'])->name('configuracoes.index');

        // Contatos
        Route::get('/contatos', [ContatoController::class, 'index'])->name('contatos.index');
        Route::delete('/contatos/{id}', [ContatoController::class, 'destroy'])->name('contatos.destroy');

        // Eventos
        Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
        Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show')->withTrashed();
        Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit')->withTrashed();
        Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update')->withTrashed();
        Route::patch('/eventos/{evento}', [EventoController::class, 'update'])->withTrashed();
        Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy')->withTrashed();

        // Pessoas — listagem, busca e CRUD
        Volt::route('/pessoas', 'pessoas.index')->name('pessoas.index');
        Route::get('/pessoas/create', [PessoaController::class, 'create'])->name('pessoas.create');
        Route::post('/pessoas', [PessoaController::class, 'store'])->name('pessoas.store');
        Route::delete('/pessoas/{pessoa}', [PessoaController::class, 'destroy'])->name('pessoas.destroy');

        // Executado diariamente via command
        Route::get('/aniversario', [AniversarioController::class, 'index'])->name('aniversario.index');

    });

    Route::middleware(['role:admin,espec,visit'])->group(function () {

        Route::middleware(['espec.movimento:2'])->group(function () {
            // Fichas VEM — listagem, aprovação e CRUD
            Route::get('/fichas/vem', [FichaVemController::class, 'index'])->name('vem.index');
            Route::get('fichas/vem/{id}/approve', [FichaVemController::class, 'approve'])->name('vem.approve');
            Route::post('fichas/vem/{id}/situacao', [FichaVemController::class, 'updateSituacao'])->name('vem.situacao');
            Route::get('/fichas/vem/create', [FichaVemController::class, 'create'])->name('vem.create');
            Route::get('/fichas/vem/{vem}', [FichaVemController::class, 'show'])->name('vem.show');
            Route::get('/fichas/vem/{vem}/edit', [FichaVemController::class, 'edit'])->name('vem.edit');
            Route::put('/fichas/vem/{vem}', [FichaVemController::class, 'update'])->name('vem.update');
            Route::patch('/fichas/vem/{vem}', [FichaVemController::class, 'update']);
            Route::delete('/fichas/vem/{vem}', [FichaVemController::class, 'destroy'])->name('vem.destroy');
        });

        Route::middleware(['espec.movimento:1'])->group(function () {
            // Fichas ECC — listagem, aprovação e CRUD
            Route::get('/fichas/ecc', [FichaEccController::class, 'index'])->name('ecc.index');
            Route::get('fichas/ecc/{id}/approve', [FichaEccController::class, 'approve'])->name('ecc.approve');
            Route::post('fichas/ecc/{id}/situacao', [FichaEccController::class, 'updateSituacao'])->name('ecc.situacao');
            Route::get('/fichas/ecc/create', [FichaEccController::class, 'create'])->name('ecc.create');
            Route::get('/fichas/ecc/{ecc}', [FichaEccController::class, 'show'])->name('ecc.show');
            Route::get('/fichas/ecc/{ecc}/edit', [FichaEccController::class, 'edit'])->name('ecc.edit');
            Route::put('/fichas/ecc/{ecc}', [FichaEccController::class, 'update'])->name('ecc.update');
            Route::patch('/fichas/ecc/{ecc}', [FichaEccController::class, 'update']);
            Route::delete('/fichas/ecc/{ecc}', [FichaEccController::class, 'destroy'])->name('ecc.destroy');
        });

        Route::middleware(['espec.movimento:3'])->group(function () {
            // Fichas SGM — listagem, aprovação e CRUD
            Route::get('/fichas/sgm', [FichaSGMController::class, 'index'])->name('sgm.index');
            Route::get('fichas/sgm/{id}/approve', [FichaSGMController::class, 'approve'])->name('sgm.approve');
            Route::post('fichas/sgm/{id}/situacao', [FichaSGMController::class, 'updateSituacao'])->name('sgm.situacao');
            Route::get('/fichas/sgm/create', [FichaSGMController::class, 'create'])->name('sgm.create');
            Route::get('/fichas/sgm/{sgm}', [FichaSGMController::class, 'show'])->name('sgm.show');
            Route::get('/fichas/sgm/{sgm}/edit', [FichaSGMController::class, 'edit'])->name('sgm.edit');
            Route::put('/fichas/sgm/{sgm}', [FichaSGMController::class, 'update'])->name('sgm.update');
            Route::patch('/fichas/sgm/{sgm}', [FichaSGMController::class, 'update']);
            Route::delete('/fichas/sgm/{sgm}', [FichaSGMController::class, 'destroy'])->name('sgm.destroy');
        });
    });

    Route::middleware(['role:admin,espec'])->group(function () {
        Route::post('/fichas/{id}/designar-visitador', function (\Illuminate\Http\Request $request, $id) {
            $request->validate([
                'idt_pessoa_visitacao' => 'nullable|exists:pessoa,idt_pessoa',
            ]);

            $ficha = \App\Models\Ficha::findOrFail($id);
            $ficha->update([
                'idt_pessoa_visitacao' => $request->input('idt_pessoa_visitacao') ?: null,
            ]);

            return redirect()->back()->with('success', 'Responsável pela visitação designado com sucesso!');
        })->name('fichas.designar-visitador');
    });

    Route::middleware(['role:admin,visit'])->group(function () {
        Volt::route('/minhas-fichas', 'minhas-fichas.index')->name('minhas-fichas.index');
    });

    Route::middleware(['role:admin,sales'])->group(function () {
        Volt::route('/mercadinho/{evento?}', 'mercadinho.index')->name('mercadinho.index');
    });

    Route::middleware(['role:admin'])->group(function () {

        // Configurações
        Route::get('/configuracoes/role', [TipoPerfilController::class, 'index'])->name('role.index');
        Route::post('/configuracoes/role', [TipoPerfilController::class, 'store'])->name('role.store');
        Route::post('/configuracoes/role/change', [TipoPerfilController::class, 'change'])->name('role.change');

        Route::resources([
            'configuracoes/equipe' => TipoEquipeController::class,
        ]);
    });
});

require __DIR__.'/auth.php';
