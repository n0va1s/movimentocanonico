<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que as rotas públicas de formulário de ficha
 * sejam acessadas apenas a partir da welcome page.
 *
 * Funcionamento:
 *  - O HomeController::index() grava na sessão o token 'welcome_access_token'.
 *  - Ao acessar /vem, /ecc ou /sgm, este middleware valida o token.
 *  - O token é de uso único: consumido após a primeira leitura.
 *  - Acesso direto (sem token) redireciona para a home com mensagem de aviso.
 */
class EnsureFromWelcome
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('welcome_access_token')) {
            return redirect()
                ->route('home')
                ->with('info', 'Para acessar o formulário de inscrição, utilize os botões disponíveis na página inicial.');
        }

        // Consome o token (uso único) para que o usuário não possa
        // navegar diretamente para outra ficha sem voltar pela home.
        $request->session()->forget('welcome_access_token');

        return $next($request);
    }
}
