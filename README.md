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
| UI framework | vue | 3.5 |
| Page client | @inertiajs/vue3 | 3.11 |
| Styling | bootstrap (CSS only, dark mode) | 5.3 |
| JS lint | eslint / eslint-plugin-vue | 10.14 / 10.12 |
| JS tests | vitest / @vue/test-utils / jsdom | 5.1 / 2.5 / 31 |

TipTap arrives with the entries work.

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
resources/css/app.css       Bootstrap import plus the app's colour tokens
resources/js/app.js         Inertia bootstrap; gives every page AppLayout unless it opts out
resources/js/pages/         One Vue file per Inertia page, named by route (auth/login, timeline/index)
resources/js/layouts/       AppLayout (top bar, search, shortcuts) and AuthLayout (centred card)
resources/js/components/    Shared Vue components
resources/js/composables/   Shared logic such as useShortcuts; *.test.js files sit beside the code
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

Locally the seeder creates `test@example.com` / `password`; outside `local` and `testing` it creates nothing. There is no public sign-up; add users with `php artisan user:create`.

No env vars beyond the `.env.example` defaults are required for local work. Password-reset emails go to the `log` mailer by default (`storage/logs/laravel.log`).

## Deploying to a host

Needs PHP 8.5 with the `pdo_sqlite` extension (SQLite built with FTS5), Composer, and Node 26 to build assets. Point the web server's document root at `public/`.

```sh
git clone <repo-url> tech-log && cd tech-log
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan user:create          # first login; do not run db:seed here
php artisan optimize
```

Set these in `.env` before going live:

| Variable | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (true leaks config and stack traces) |
| `APP_URL` | the public URL; password-reset links are built from it |
| `MAIL_MAILER` and `MAIL_*` | a real mailer, or reset emails only reach the log |
| `SESSION_SECURE_COOKIE` | `true` when served over HTTPS |

The web server user must be able to write `storage/`, `bootstrap/cache/`, and both `database/database.sqlite` and the `database/` directory (SQLite writes a journal file beside the database). To update: `git pull`, then rerun the `composer install`, `npm ci && npm run build`, `migrate --force` and `optimize` steps.

## Tests and linting

```sh
php artisan test                                   # full suite
php artisan test tests/Feature/Api/V1/TokenTest.php # one file
php artisan test --filter="revokes the current token" # one test
vendor/bin/pint            # format PHP
vendor/bin/pint --test     # check only
npm test                   # all Vitest tests
npx vitest run resources/js/composables/useShortcuts.test.js  # one file
npx vitest run -t "closes on Esc"                             # one test
npm run lint               # ESLint (add -- --fix to apply fixes)
```

Feature tests use an in-memory SQLite database with `RefreshDatabase`, and call `withoutVite()` where they render pages, so they run without a frontend build.

## Architecture notes

- **Two surfaces, one app.** The browser gets Inertia pages from `routes/web.php` with session auth. External clients use `/api/v1` with Sanctum bearer tokens issued by `POST /api/v1/tokens`. Business logic belongs in `app/Actions` so both controller sets call the same code.
- **Fortify owns the auth routes** (`/login`, `/logout`, `/forgot-password`, `/reset-password/{token}`, `/two-factor-challenge`, `/user/confirm-password`). They don't appear in `routes/web.php`; run `php artisan route:list` to see them. Their GET screens are wired to Inertia pages in `FortifyServiceProvider`.
- **Token requests share the `login` rate limiter** (5 per minute per email and IP) defined in `FortifyServiceProvider`.
- **Layouts are applied in `app.js`.** A page gets `AppLayout` by default; auth pages declare `defineOptions({ layout: false })` and wrap themselves in `AuthLayout`. Inertia keeps a persistent layout mounted across visits, so the search box and shortcuts survive navigation.
- **Shared props** (`auth.user`, `flash.status`) come from `HandleInertiaRequests::share()`. Only `id`, `name` and `email` of the user are exposed.
- **Single-key shortcuts** go through `useShortcuts`, which ignores keys typed into inputs or the editor and any key held with Ctrl, Cmd or Alt.
- **Bootstrap JavaScript is not loaded.** Menus and dialogs are Vue state toggling Bootstrap's classes, so there's one source of truth for what's open.
- **Any page render needs a Vite build** (`npm run build`) or a running `npm run dev`; without one the root view throws "Vite manifest not found".

## Conventions

- One feature branch per task, off `master`, merged by fast-forward.
- Commit messages contain no tool attribution.
- Tests first where practical; run the suite and Pint before calling work done.
