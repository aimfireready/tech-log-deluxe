# Tech Log

How to work on this codebase. For what the product is and where it stands, see [PROJECT.md](PROJECT.md). Agent instructions live in [CLAUDE.md](CLAUDE.md); incident history lives in [MISTAKES.md](MISTAKES.md) once it exists.

## Stack

| Layer | Package | Version |
| --- | --- | --- |
| Language | PHP | 8.5 |
| Framework | laravel/framework | 13.40 |
| Page delivery | inertiajs/inertia-laravel | 3.7 |
| Web auth backend | laravel/fortify | 1.41 |
| API token auth | laravel/sanctum | 4.3 |
| Tests | pestphp/pest | 5.3 |
| PHP lint | laravel/pint | 1.32 |
| Database | SQLite (with FTS5) | 3.54 |
| Build | Vite / laravel-vite-plugin | 8 / 3.1 |
| Node | Node.js | 26 |

The Vue 3, Bootstrap 5, TipTap and ESLint layer is not installed yet; `package.json` still carries the Laravel skeleton's Tailwind defaults until the frontend scaffold replaces them.

## Repo layout

```
app/
  Actions/Fortify/          Fortify's user actions (password reset, profile, password update)
  Http/Controllers/         Inertia page controllers
  Http/Controllers/Api/V1/  REST API controllers (/api/v1)
  Http/Requests/            Form Requests, shared by both surfaces where possible
  Http/Resources/           API Resources shaping REST output
  Http/Middleware/          HandleInertiaRequests (shared Inertia props)
  Providers/                FortifyServiceProvider maps auth screens to Inertia pages
config/fortify.php          Which auth features are on
database/                   Migrations, factories, seeders, local database.sqlite
resources/views/app.blade.php  Inertia root template (the only Blade view)
resources/js/               Vue app; pages go in resources/js/pages
routes/web.php              Inertia page routes (session auth)
routes/api.php              /api/v1 routes (Sanctum tokens)
tests/Feature/              Pest feature tests, one folder per surface
```

## Setup

```sh
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer dev            # Laravel server, queue, logs and Vite together
```

The seeder creates `test@example.com` / `password`. There is no public sign-up; add users with a seeder or `php artisan tinker`.

No env vars beyond the `.env.example` defaults are required for local work. Password-reset emails go to the `log` mailer by default (`storage/logs/laravel.log`).

## Tests and linting

```sh
php artisan test                                   # full suite
php artisan test tests/Feature/Api/V1/TokenTest.php # one file
php artisan test --filter="revokes the current token" # one test
vendor/bin/pint            # format PHP
vendor/bin/pint --test     # check only
```

Feature tests use an in-memory SQLite database with `RefreshDatabase`, and call `withoutVite()` where they render pages, so they run without a frontend build.

## Architecture notes

- **Two surfaces, one app.** The browser gets Inertia pages from `routes/web.php` with session auth. External clients use `/api/v1` with Sanctum bearer tokens issued by `POST /api/v1/tokens`. Business logic belongs in `app/Actions` so both controller sets call the same code.
- **Fortify owns the auth routes** (`/login`, `/logout`, `/forgot-password`, `/reset-password/{token}`, `/two-factor-challenge`, `/user/confirm-password`). They don't appear in `routes/web.php`; run `php artisan route:list` to see them. Their GET screens are wired to Inertia pages in `FortifyServiceProvider`.
- **Token requests share the `login` rate limiter** (5 per minute per email and IP) defined in `FortifyServiceProvider`.
- **Any page render needs a Vite build** (`npm run build`) or a running `npm run dev`; without one the root view throws "Vite manifest not found".

## Conventions

- One feature branch per task, off `master`, merged by fast-forward.
- Commit messages contain no tool attribution.
- Tests first where practical; run the suite and Pint before calling work done.
