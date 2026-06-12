<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictEspecMovement
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $movimento): Response
    {
        $user = auth()->user();

        if ($user && $user->isEspec()) {
            if (is_null($user->idt_movimento) || (int) $user->idt_movimento !== $movimento) {
                abort(403, 'Acesso não autorizado para este movimento.');
            }
        }

        return $next($request);
    }
}
