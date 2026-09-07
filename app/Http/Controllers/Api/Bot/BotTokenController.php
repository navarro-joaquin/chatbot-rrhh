<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BotTokenController extends Controller
{
    public function store(): JsonResponse
    {
        $email = (string) config('services.rrhh_bot.user_email', '');

        if ($email === '') {
            return response()->json([
                'message' => 'El usuario técnico del bot no está configurado.',
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
                ->where('name', 'rrhh-bot')
                ->where('expires_at', '<=', now())
                ->delete();

            $expiresAt = now()->addDay();
            $token = $user->createToken('rrhh-bot', ['bot:read'], $expiresAt);

            return [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toISOString(),
            ];
        });

        if ($tokenData === null) {
            return response()->json([
                'message' => 'El usuario técnico configurado para el bot no existe.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['data' => $tokenData]);
    }
}
