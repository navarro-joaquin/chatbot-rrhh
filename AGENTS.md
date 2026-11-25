# AGENTS.md

Laravel 12 HR app with a Spanish domain and UI. Livewire 4 full-page single-file components + Flux UI Free + Tailwind v4 + PowerGrid v6. PHP `^8.2` (CI: 8.4/8.5), Pest 4, Pint (`laravel` preset). App timezone `America/La_Paz`, locale `es`.

## Commands

- Setup: `composer setup` (install + `.env` + key + migrate + `npm run build`). It does **not** seed; run `php artisan db:seed` for `StartupSeeder` (gestiones 2016–2027, tramos de antigüedad, admin `admin@admin.com` / `Passw0rd`).
- Dev: `composer dev` = `artisan serve` + `queue:listen` + `npm run dev`; `npm run dev` alone misses server/queue. UI not reflecting (or Vite manifest error) → `npm run build`.
- Verify: `composer test` = `config:clear` + `pint --test` + full Pest (~15 s). Fix style: `composer lint` (`pint --parallel`) or `vendor/bin/pint --dirty`.
- Single test: `php artisan test --compact --filter=Nombre` or `vendor/bin/pest tests/Feature/FooTest.php --compact`.
- Create files via `php artisan make:* --no-interaction`. `make:livewire` defaults to SFC (`⚡name.blade.php`); pass `--class` for class-based components like `app/Livewire/*Table.php`.
- Schedules live in `routes/console.php` and only fire via cron/`php artisan schedule:work`; commands in `app/Console/Commands/` auto-register. Check existing entries before adding one.
- CI (`.github/workflows/tests.yml`, `lint.yml`; branches `develop/main/master/workos`): `composer install` needs `FLUX_USERNAME`/`FLUX_LICENSE_KEY` secrets to auth `composer.fluxui.dev`; tests run `pest`, lint runs `composer lint` (it does not auto-commit).

## Architecture

- Pages (`routes/web.php`) are `Route::livewire('url', 'name')`; the component is `resources/views/components/⚡{name}.blade.php` — a Livewire 4 single-file component whose `<?php new class extends Component` block holds the logic. `⚡` is part of the filename; route and `Livewire::test('name')` references omit it. Starter settings pages live in `resources/views/pages/settings/`, routed as `pages::settings.*`.
- Tables are `app/Livewire/*Table.php` (`PowerGridComponent`), one per page. Pages render `<livewire:foo-table />` and refresh after writes with `$this->dispatch('pg:eventRefresh-{tableName}')` (the component's `$tableName`, e.g. `pg:eventRefresh-vacaciones-table`). Tables set `config(['livewire-powergrid.filter' => 'outside'])` in `boot()`; keep that.
- Validation lives in Livewire Form objects (`app/Livewire/Forms/*Form.php`: array rules + `messages()` + `validationAttributes()`), not Form Requests. `app/Http/Requests/` only holds `GestionRequest` and the integration list filters.
- Keep the Spanish domain vocabulary: `empleado`, `gestion`, `vacacion`, `compensacion`, `antiguedad`, `feriado` — for models, columns, methods and labels.
- Balance changes go through `VacacionService` / `CompensacionService` (transactions, FIFO discounting, `SaldoInsuficienteException`); never edit `dias_disponibles` / `cantidad_horas` directly from a component.
- `App\Support\VacacionesTiempo` is the single source for day/hour/minute math (8 h = 1 day); reuse `aDias` / `aPartesDias` / `aTextoDias`.
- Domain models extend `App\Models\BaseModel` for spatie activity logging (`logAll` + dirty only) — new domain models should too. `User` overrides log options to strip secrets; `Empleado` uses SoftDeletes and restoring it is admin-only.
- Perfiles: `users.rol` cast to `App\Enums\Rol` (`administrador` | `rrhh`). Gates `gestionar-usuarios` and `restaurar-empleados` are admin-only (`app/Providers/AppServiceProvider.php`). No public registration; users are created from `usuarios`. `UserFactory` defaults to `rrhh`; use `->administrador()`.
- `v_historial_vacaciones` is a SQL view created by migrations and read with `DB::table` in `HistorialVacacionTable` — the intentional exception to Eloquent-only. Changing a history source means updating the view in a new migration.
- Read-only bot API (`routes/api.php`, `docs/bot-api.md`): `/api/v1/bot/*`, Sanctum `bot:read` + `bot.client` middleware (`X-Bot-Client-Secret` = `RRHH_BOT_CLIENT_SECRET`). Token endpoint throttled `6,1`, 24 h expiry; setup `app:crear-usuario-bot` (`app:crear-token-bot` is diagnostic only).
- Read-only integration API (`docs/integracion-api.md`): `/api/v1/integracion/*` (empleados, feriados, solicitudes, usuarios + `POST usuarios/verificar-credenciales`), `integracion:read` + `integracion.client` (`X-Integracion-Client-Secret` = `RRHH_INTEGRACION_CLIENT_SECRET`); fully separate from the bot API. Setup `app:crear-usuario-integracion`.
- Laravel 12 layout: middleware aliases in `bootstrap/app.php`; schedule in `routes/console.php` (`app:procesar-vacaciones-anuales` and `app:procesar-fin-contrato` daily `00:00`, `app:procesar-fin-gestion` yearly 12-31 `23:59`). Exports use `openspout/openspout`.

## Conventions

- `env()` only inside `config/`; use `config(...)` elsewhere. Curly braces, explicit return types, promoted constructors, PHPDoc over inline comments. Named routes + `route()`; eager-load to avoid N+1.
- Tests: `tests/Pest.php` already applies `Tests\TestCase` + `RefreshDatabase` to everything in `tests/Feature`, so don't re-add either there. Tests use sqlite `:memory:` (`phpunit.xml`); dev uses the sqlite file. Only `Empleado`, `User` and `SolicitudVacacion` have factories, and some (e.g. `SolicitudVacacionFactory`) are empty stubs — build fixtures with `Model::create`. Prefer feature tests, add a test with each behavior change, and don't delete tests without approval.
- Don't add or change dependencies, or add top-level directories, without approval. Documentation is Spanish `.md` under `docs/`; only create docs when asked.
- Full Laravel Boost guidelines live in `GEMINI.md` (mirrored in `.junie/guidelines.md`); this file is the compact, repo-specific version.
