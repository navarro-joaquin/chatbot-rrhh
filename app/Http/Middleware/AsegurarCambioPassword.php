<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a los usuarios marcados con `debe_cambiar_contrasena` a definir una
 * nueva contraseña antes de usar cualquier otra página de la aplicación.
 */
class AsegurarCambioPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->debe_cambiar_contrasena) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, 'Debe cambiar su contraseña antes de continuar.');
            }

            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
