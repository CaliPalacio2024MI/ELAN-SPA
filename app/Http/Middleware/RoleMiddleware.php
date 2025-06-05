<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Verifica que el usuario autenticado tenga alguno de los roles permitidos.
     * Si no, aborta con código 403.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user || !in_array($user->rol, $roles)) {
            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}
