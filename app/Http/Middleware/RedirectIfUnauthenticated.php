<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class RedirectIfUnauthenticated extends Middleware
{
    /**
     * Redirige a la ruta de login cuando el usuario no está autenticado.
     */
    protected function redirectTo(Request $request): ?string
    {
        return route('login');
    }
}
