<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.rrhh_bot.client_secret' => 'test-client-secret',
        'services.rrhh_bot.user_email' => 'rrhh-bot@internal.local',
    ]);
});

it('rechaza la emisión sin la credencial de servicio', function () {
    $this->postJson('/api/v1/bot/auth/token')
        ->assertUnauthorized();
});

it('emite un token bot read válido durante un día', function () {
    $user = User::factory()->create([
        'email' => 'rrhh-bot@internal.local',
    ]);

    $response = $this->withHeader('X-Bot-Client-Secret', 'test-client-secret')
        ->postJson('/api/v1/bot/auth/token')
        ->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'expires_at'],
        ]);

    $token = $user->tokens()->sole();

    expect($token->abilities)->toBe(['bot:read'])
        ->and($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->between(
            now()->addHours(23),
            now()->addHours(25),
        ))->toBeTrue();

    $this->withToken((string) $response->json('data.access_token'))
        ->getJson('/api/v1/bot/empleados/por-telefono/00000000')
        ->assertNotFound();
});

it('elimina tokens vencidos antes de emitir uno nuevo', function () {
    $user = User::factory()->create([
        'email' => 'rrhh-bot@internal.local',
    ]);
    $user->createToken('rrhh-bot', ['bot:read'], now()->subMinute());

    $this->withHeader('X-Bot-Client-Secret', 'test-client-secret')
        ->postJson('/api/v1/bot/auth/token')
        ->assertOk();

    expect($user->tokens()->count())->toBe(1);
});

it('informa cuando el usuario técnico configurado no existe', function () {
    $this->withHeader('X-Bot-Client-Secret', 'test-client-secret')
        ->postJson('/api/v1/bot/auth/token')
        ->assertServiceUnavailable();
});

it('crea un usuario técnico sin contraseña interactiva', function () {
    $this->artisan('app:crear-usuario-bot')
        ->expectsOutputToContain('Usuario técnico creado')
        ->assertSuccessful();

    $user = User::query()
        ->where('email', 'rrhh-bot@internal.local')
        ->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->not->toBeEmpty();
});
