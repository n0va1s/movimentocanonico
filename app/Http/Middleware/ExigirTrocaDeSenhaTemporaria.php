<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirTrocaDeSenhaTemporaria
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->senha_temporaria_expira_em
            && ! $request->routeIs('primeira-senha.*', 'logout')) {
            return redirect()->route('primeira-senha.edit');
        }

        return $next($request);
    }
}
