<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integracion\ListarUsuariosRequest;
use App\Http\Requests\Api\Integracion\VerificarCredencialesRequest;
use App\Http\Resources\Api\Integracion\UsuarioResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UsuarioController extends Controller
{
    /**
     * Intentos fallidos permitidos por correo antes de bloquear la verificación.
     */
    private const INTENTOS_MAXIMOS = 5;

    /**
     * Segundos que dura el bloqueo tras superar los intentos permitidos.
     */
    private const SEGUNDOS_BLOQUEO = 60;

    public function index(ListarUsuariosRequest $request): AnonymousResourceCollection
    {
        $usuarios = $this->usuariosVisibles()
            ->when($request->filled('rol'), fn (Builder $query) => $query->where('rol', $request->string('rol')->toString()))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return UsuarioResource::collection($usuarios);
    }

    public function show(int $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuariosVisibles()->findOrFail($usuario));
    }

    /**
     * Comprueba correo y contraseña de un usuario sin iniciar sesión ni emitir tokens,
     * para que la aplicación consumidora reutilice las credenciales de RR. HH.
     */
    public function verificarCredenciales(VerificarCredencialesRequest $request): JsonResponse|UsuarioResource
    {
        $email = Str::lower($request->string('email')->toString());
        $claveLimite = 'integracion-credenciales|'.Str::transliterate($email);

        if (RateLimiter::tooManyAttempts($claveLimite, self::INTENTOS_MAXIMOS)) {
            return response()->json([
                'message' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($claveLimite).' segundos.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $usuario = $this->usuariosVisibles()->where('email', $email)->first();

        if (! $usuario || ! Hash::check($request->string('password')->toString(), $usuario->password)) {
            RateLimiter::hit($claveLimite, self::SEGUNDOS_BLOQUEO);

            return response()->json([
                'message' => 'Las credenciales no son válidas.',
                'errors' => ['email' => ['Las credenciales no son válidas.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        RateLimiter::clear($claveLimite);

        return new UsuarioResource($usuario);
    }

    /**
     * Usuarios de personas: excluye los usuarios técnicos del bot y de la integración.
     *
     * @return Builder<User>
     */
    private function usuariosVisibles(): Builder
    {
        $correosTecnicos = array_values(array_filter([
            config('services.rrhh_bot.user_email'),
            config('services.rrhh_integracion.user_email'),
        ]));

        return User::query()->whereNotIn('email', $correosTecnicos);
    }
}
