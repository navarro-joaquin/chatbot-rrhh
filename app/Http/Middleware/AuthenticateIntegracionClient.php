<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegracionClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('services.rrhh_integracion.client_secret', '');
        $providedSecret = (string) $request->header('X-Integracion-Client-Secret', '');

        if ($expectedSecret === '') {
            return response()->json([
                'message' => 'La emisión de tokens para la integración no está configurada.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'message' => 'Credencial de servicio inválida.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
