<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Verifica se o usuário autenticado possui um dos perfis informados.
     *
     * Uso nas rotas:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,coord')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(
            auth()->check() && auth()->user()->hasRole(...$roles),
            403
        );

        return $next($request);
    }
}
