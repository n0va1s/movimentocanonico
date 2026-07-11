<?php

namespace App\Providers;

use App\Enums\Perfil;
use App\Models\Gamificacao;
use App\Observers\GamificacaoObserver;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gamificacao::observe(GamificacaoObserver::class);
        \App\Models\Ficha::observe(\App\Observers\FichaObserver::class);

        $this->registrarGates();
    }

    /**
     * Registra todos os Gates de autorização da aplicação.
     */
    private function registrarGates(): void
    {
        // Área administrativa global
        Gate::define('acessar-configuracoes', fn ($user) => $user->isAdmin());
        Gate::define('acessar-contatos', fn ($user) => $user->isAdmin());
        Gate::define('gerenciar-eventos', fn ($user) => $user->isAdmin());

        // Acesso ao painel de gerenciamento de um evento (qualquer aba)
        Gate::define('acessar-gerenciamento-evento', function ($user, $evento) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isDirig() || $user->isCoordenador()) {
                return ! is_null($user->idt_movimento) && (int) $evento->idt_movimento === (int) $user->idt_movimento;
            }

            return false;
        });

        // Abas do gerenciamento — coord e dirig só têm acesso se forem do mesmo movimento
        foreach (Perfil::abasPermitidas() as $aba => $perfisPermitidos) {
            Gate::define("evento-tab-{$aba}", function ($user, $evento) use ($perfisPermitidos) {
                if (! $user->hasRole(...$perfisPermitidos)) {
                    return false;
                }

                // admin passa direto
                if ($user->isAdmin()) {
                    return true;
                }

                if ($user->isDirig() || $user->isCoordenador()) {
                    return ! is_null($user->idt_movimento) && (int) $evento->idt_movimento === (int) $user->idt_movimento;
                }

                return $user->trabalhaNoEvento($evento->idt_evento);
            });
        }
    }

    /**
     * Middleware para geracao do traceId para todas as requisicoes
     */
    public function configure(Middleware $middleware): void
    {
        //
    }
}
