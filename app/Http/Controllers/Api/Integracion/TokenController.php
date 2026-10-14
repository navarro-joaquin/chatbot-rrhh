<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TokenController extends Controller
{
    public function store(): JsonResponse
    {
        $email = (string) config('services.rrhh_integracion.user_email', '');

        if ($email === '') {
            return response()->json([
                'message' => 'El usuario técnico de la integración no está configurado.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $tokenData = DB::transaction(function () use ($email): ?array {
            $user = User::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            $user->tokens()
                ->where('name', 'rrhh-integracion')
                ->where('expires_at', '<=', now())
                ->delete();

            $expiresAt = now()->addDay();
            $token = $user->createToken('rrhh-integracion', ['integracion:read'], $expiresAt);

            return [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toISOString(),
            ];
        });

        if ($tokenData === null) {
            return response()->json([
                'message' => 'El usuario técnico configurado para la integración no existe.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['data' => $tokenData]);
    }
}
