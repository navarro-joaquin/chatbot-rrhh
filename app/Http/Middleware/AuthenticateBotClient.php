<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBotClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('services.rrhh_bot.client_secret', '');
        $providedSecret = (string) $request->header('X-Bot-Client-Secret', '');

        if ($expectedSecret === '') {
            return response()->json([
                'message' => 'La emisión de tokens para el bot no está configurada.',
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
