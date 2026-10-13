# AGENTS.md

Laravel 12 HR app (Livewire 4 full-page + Flux UI Free + Tailwind v4 + PowerGrid v6). PHP `^8.2` (CI: 8.4/8.5). Pest 4 + Pint (`laravel` preset).

## Commands

- Setup: `composer setup` (install + key + migrate + `npm run build`).
- Dev (serve + queue + vite): `composer dev` or `npm run dev`. Frontend not reflecting? Run `npm run build`.
- Verify: `composer test` = `config:clear` + `pint --test` + `php artisan test`. Lint fix: `composer lint` (`pint --parallel`).
- Single test: `php artisan test --compact --filter=TestName` or `./vendor/bin/pest --filter=... --compact`.
- Make files via artisan: `php artisan make:<type> --no-interaction` (model, test `--pest`/`--unit`, class). Check `routes/console.php` commands before adding schedulers.

## Architecture

- Web (`routes/web.php`): all pages are `Route::livewire(...)` full-page components; tables live in `app/Livewire/*Table.php` (PowerGrid), forms in `app/Livewire/Forms/`.
- Domain: models `app/Models/` (Empleado, Vacacion, Compensacion, Solicitud*, Gestion, Feriado...); business rules in `app/Services/VacacionService.php`, `CompensacionService.php` + `app/Support/VacacionesTiempo.php` (8h-day days/hours/minutes + `texto` breakdown — reuse, don't reimplement).
- Read-only bot API (`routes/api.php`, `docs/bot-api.md`): `/api/v1/bot/*`, Sanctum `bot:read` ability + `bot.client` middleware (`X-Bot-Client-Secret` = `RRHH_BOT_CLIENT_SECRET`). Token endpoint throttled `6,1`, 24h expiry.
- Laravel 12 structure: middleware aliases in `bootstrap/app.php`, schedule in `routes/console.php` (vacaciones-anuales + fin-contrato daily `00:00`, fin-gestion yearly 12-31). Cron commands: `app:procesar-vacaciones-anuales`, `app:procesar-fin-contrato`, `app:procesar-fin-gestion`; bot setup: `app:crear-usuario-bot`, `app:crear-token-bot` (diagnostic only).
- Exports use `openspout/openspout`; audit via `spatie/laravel-activitylog`.

## Conventions

- `env()` only inside `config/`; use `config(...)` elsewhere. Curly braces always, explicit return types, PHP 8 constructor promotion, PHPDoc over inline comments (`GEMINI.md`).
- Validation in Form Requests, Eloquent + eager loading over `DB::`, named routes + `route()`, queued jobs (`ShouldQueue`) for slow ops, `<flux:*>` components for UI.
- Tests: Pest, `RefreshDatabase` on Feature suite, sqlite `:memory:` (`phpunit.xml`); default DB is sqlite file. Use model factories; prefer feature tests. Don't delete tests without approval.
- CI (`.github/workflows/tests.yml`, `lint.yml`, branches develop/main/master/workos): needs `FLUX_USERNAME`/`FLUX_LICENSE_KEY` secrets for `composer.fluxui.dev` auth before `composer install`.
