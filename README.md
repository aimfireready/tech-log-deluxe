# Tech Log

How to work on this codebase. For what the product is and where it stands, see [PROJECT.md](PROJECT.md). Agent instructions live in [CLAUDE.md](CLAUDE.md); incident history is kept locally in a git-ignored `MISTAKES.md`.

## Stack

| Layer | Package | Version |
| --- | --- | --- |
| Language | PHP | 8.4 or newer (tested on 8.4 and 8.5) |
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
| HTML sanitizing | symfony/html-sanitizer | 8.1 |
| Editor | @tiptap/vue-3, starter-kit, extension-image | 3.33 |

## Repo layout

```
app/
  Actions/Entries/          SaveEntry (create/update) and TimelineQuery (listing, search, filters)
  Actions/Attachments/      StoreAttachment (editor image uploads)
  Actions/Fortify/          Fortify's user actions (password reset, profile, password update)
  Support/                  EntryHtml (sanitize, html->text) and SearchQuery (safe FTS5 MATCH)
  Rules/                    Custom validation rules
  Console/Commands/         user:create
  Http/Controllers/         Inertia page controllers
  Http/Controllers/Api/V1/  REST API controllers (/api/v1)
  Http/Requests/            Form Requests, shared by both surfaces where possible
  Http/Resources/           API Resources shaping REST output
  Http/Middleware/          HandleInertiaRequests (shared Inertia props), AllowReaderKey (/read access)
  Mcp/Servers/              TechLogServer: the MCP server Claude Desktop talks to
  Mcp/Tools/                One class per MCP tool (search, get, list tags, create)
  Providers/                FortifyServiceProvider maps auth screens to Inertia pages
config/fortify.php          Which auth features are on
config/techlog.php          App settings: the MCP server's acting user and timezone, the /read share key
config/trustedproxy.php     Reverse proxies whose X-Forwarded-* headers are trusted (TRUSTED_PROXIES)
database/                   Migrations, factories, seeders, local database.sqlite
resources/views/app.blade.php  Inertia root template (the only Blade view)
resources/css/app.css       Bootstrap import plus the app's colour tokens
resources/css/reader.css    The reading view's own styles, all scoped under .gh
resources/js/app.js         Inertia bootstrap; gives every page AppLayout unless it opts out
resources/js/pages/         One Vue file per Inertia page, named by route (auth/login, timeline/index)
resources/js/layouts/       AppLayout (top bar, search, shortcuts), AuthLayout (centred card), ReaderLayout (/read)
resources/js/components/    Shared Vue components; reader/ holds the reading view's post cards and feed
resources/js/composables/   Shared state and logic (useShortcuts, useTimeline, useQuickAdd)
resources/js/utils/         Plain functions: dates, tags, uploads; *.test.js files sit beside the code
routes/web.php              Inertia page routes (session auth)
routes/api.php              /api/v1 routes (Sanctum tokens)
routes/ai.php               MCP server registration (local stdio handle "techlog")
tests/Feature/              Pest feature tests, one folder per surface
Dockerfile                  Production image: Node stage builds assets, PHP 8.4 + Apache stage serves public/
docker/                     The image's entrypoint (storage setup, migrate, optimize) and php.ini
docker-compose.yml          Runs the image with its port, storage volume and .env.docker settings
```

## Setup

```sh
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link   # serves uploaded images from /storage
composer dev            # Laravel server, queue, logs and Vite together
```

Locally the seeder creates `test@example.com` / `password` and 15 sample entries; outside `local` and `testing` it creates nothing. There is no public sign-up; add users with `php artisan user:create`.

With Laravel Herd the site is served at `http://tech-log-deluxe.test`. Pin it to a PHP version with `herd isolate 8.4`: an unpinned site uses Herd's default PHP, and if no default is set (seen after a reboot) Herd answers every request with a 502.

No env vars beyond the `.env.example` defaults are required for local work. Password-reset emails go to the `log` mailer by default (`storage/logs/laravel.log`).

## Deploying to a host

Needs PHP 8.4 or newer with the `pdo_sqlite` extension (SQLite built with FTS5), Composer, and Node 26 to build assets. Point the web server's document root at `public/`.

```sh
git clone <repo-url> tech-log && cd tech-log
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link         # once; uploaded images are served from /storage
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
| `TECHLOG_MCP_USER` | optional: email of the user MCP-created entries are credited to; unset leaves the MCP server read-only |
| `TECHLOG_MCP_TIMEZONE` | optional: IANA timezone for MCP search date filters, e.g. `America/Indiana/Indianapolis` (default `UTC`) |
| `TRUSTED_PROXIES` | optional: comma-separated IPs/CIDRs (or `*`) of a TLS-terminating reverse proxy, so URLs and secure cookies use https; unset trusts none |
| `TECHLOG_READ_KEY` | optional: share key for the reading view; `/read?key=<value>` opens it without a login. Generate with `php -r 'echo bin2hex(random_bytes(24)), PHP_EOL;'`; unset requires a login |

Uploaded images live in `storage/app/public/attachments` and are not in git; back them up with the database. The web server user must be able to write `storage/`, `bootstrap/cache/`, and both `database/database.sqlite` and the `database/` directory (SQLite writes a journal file beside the database). To update: `git pull`, then rerun the `composer install`, `npm ci && npm run build`, `migrate --force` and `optimize` steps.

## Running with Docker

The `Dockerfile` builds a single production image: a Node 26 stage runs `npm ci && npm run build`, then a `php:8.4-apache` stage installs Composer dependencies without dev packages and serves `public/`. There is no scheduler and nothing is queued, so one container is the whole app. Everything that must survive a rebuild (the SQLite database at `storage/database.sqlite`, uploaded images, logs) lives in the `/var/www/html/storage` volume; back that up.

With Compose, settings live in `.env.docker` (git-ignored; start from `.env.docker.example`) and the volume is named `storage`:

```sh
cp .env.docker.example .env.docker
docker compose run --rm app php artisan key:generate --show   # paste into APP_KEY
docker compose up -d --build                                  # also how you update after git pull
docker compose exec -u www-data app php artisan user:create
docker compose logs -f
```

The container restarts unless stopped and has a health check on `/up`. Set `TECHLOG_PORT` to publish on a port other than 8080. The plain `docker` equivalent:

```sh
docker build -t tech-log .
docker run --rm tech-log php artisan key:generate --show      # once; keep the key
docker run -d --name techlog -p 8080:80 -v techlog-storage:/var/www/html/storage \
  -e APP_KEY=base64:... -e APP_URL=https://techlog.example \
  -e TRUSTED_PROXIES=172.16.0.0/12 -e SESSION_SECURE_COOKIE=true \
  -e MAIL_MAILER=smtp -e MAIL_HOST=... \
  tech-log
docker exec -it -u www-data techlog php artisan user:create
```

On every start the entrypoint (`docker/entrypoint.sh`) recreates the storage layout on an empty volume, creates the database file if missing, runs `migrate --force`, and caches config and routes with `optimize`, so environment changes take effect on `docker restart`. It refuses to start Apache without `APP_KEY`. The image sets `APP_ENV=production`, `APP_DEBUG=false` and logs to stderr (`docker logs techlog`); pass the rest of the variables from "Deploying to a host" with `-e` or `--env-file`. Run artisan commands as `www-data` (`docker exec -u www-data`), or files they create in the volume end up owned by root.

Put a TLS-terminating proxy such as Caddy in front and set `TRUSTED_PROXIES` to the proxy's address (the Docker bridge range above, if Caddy runs on the host). For Claude Desktop, use `"command": "docker"` with `"args": ["exec", "-i", "-u", "www-data", "techlog", "php", "artisan", "mcp:start", "techlog"]`.

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

- **Three surfaces, one app.** The browser gets Inertia pages from `routes/web.php` with session auth. External clients use `/api/v1` with Sanctum bearer tokens issued by `POST /api/v1/tokens`. MCP clients such as Claude Desktop use the stdio server in `app/Mcp`. Business logic belongs in `app/Actions` so all three call the same code.
- **Fortify owns the auth routes** (`/login`, `/logout`, `/forgot-password`, `/reset-password/{token}`, `/two-factor-challenge`, `/user/confirm-password`). They don't appear in `routes/web.php`; run `php artisan route:list` to see them. Their GET screens are wired to Inertia pages in `FortifyServiceProvider`.
- **Token requests share the `login` rate limiter** (5 per minute per email and IP) defined in `FortifyServiceProvider`.
- **Layouts are applied in `app.js`.** A page gets `AppLayout` by default; auth pages declare `defineOptions({ layout: false })` and wrap themselves in `AuthLayout`. Inertia keeps a persistent layout mounted across visits, so the search box and shortcuts survive navigation.
- **Shared props** (`auth.user`, `flash.status`) come from `HandleInertiaRequests::share()`. Only `id`, `name` and `email` of the user are exposed.
- **Single-key shortcuts** go through `useShortcuts`, which ignores keys typed into inputs or the editor and any key held with Ctrl, Cmd or Alt.
- **Bootstrap JavaScript is not loaded.** Menus and dialogs are Vue state toggling Bootstrap's classes, so there's one source of truth for what's open.
- **Entries are only written through `SaveEntry`.** It sanitizes `body` with `EntryHtml::sanitize()` (an allow-list matching what the TipTap toolbar can produce), derives `body_text`, syncs tags by slug, and links uploaded images the body references. `occurred_at` is set at creation and never changes; `UpdateEntryRequest` prohibits it. There are no per-entry permissions: any signed-in user can edit or delete any entry.
- **Search is SQLite FTS5.** `entries_fts` is an external-content index over `title` and `body_text`, kept in sync by three triggers created in the `create_entries_fts_table` migration, so anything that writes `entries` (including raw SQL) stays searchable. Soft-deleted rows stay in the index and are filtered out by joining back to `entries`. User input never reaches `MATCH` directly: `SearchQuery::toMatch()` turns it into quoted prefix terms. Results stay in timeline order; matches are highlighted with `highlight()`/`snippet()` and sent as `{text, match}` segments, so no HTML from the index is rendered.
- **Days are the viewer's days.** `occurred_at` is stored in UTC. The browser sends its IANA timezone in `X-Timezone` on every Inertia request (`app.js`), and `TimelineFilterRequest::timezone()` uses it for the from/to filter boundaries; API clients pass `tz=`. The timeline groups by local day in the browser.
- **Infinite scroll** uses Inertia's `Inertia::scroll()` prop with a cursor paginator and the `<InfiniteScroll>` component. Don't pass `reset: [...]` on ordinary visits: in Inertia 3 it turns the visit into a partial reload of only that prop, so filters, flash messages and shared tags go stale.
- **Single resources passed to pages are `->resolve()`d** so the page gets fields directly instead of under `data`. Nested collections inside a resource must be resolved too (see `EntryResource` tags), or they reach the page as objects.
- **Images** are uploaded before the entry exists (`POST /attachments`, raster types only, 10 MB, content-sniffed), stored on the `public` disk under `attachments/Y/m/`, and returned as root-relative `/storage/...` URLs so they work on any host name. `entry_id` is filled when an entry body first references the file.
- **The editor is loaded on demand.** `RichEditor` (TipTap) is imported with `defineAsyncComponent`, keeping it out of the main bundle.
- **Deleting is a soft delete** with an 8-second Undo toast driven by the `flash.deleted_entry` shared prop; `POST /entries/{id}/restore` brings it back.
- **The reading view (`/read`) is a second, read-only look at the same entries**, styled after a Ghost/Casper blog: a single centred column of posts (like Casper's "List" feed layout) with search, tag archives, and post pages with older/newer links and deferred "Read more". `ReaderController` reuses `TimelineQuery`; `PostCardResource` adds `feature_image` (the first `<img>` in the body), `reading_minutes` (265 words a minute) and `primary_tag` (the first tag), and leaves the body out of feeds. Its pages set `layout: ReaderLayout`, and its CSS in `reader.css` is scoped under `.gh`, light by default and dark when the OS is, with system fonts only. Writing and editing stay in the main app; each post links back with "Edit in app". Access is checked by `AllowReaderKey`, not `auth`: signed-in users pass, and so does anyone who opens `/read?key=<TECHLOG_READ_KEY>`. A correct key is remembered in the session as a SHA-256 of the key (so changing it in `.env`, then rerunning `optimize`, cuts off every old link) and stripped from the URL with a redirect. Key visitors see no app links (`auth.user` is null), and the key opens nothing outside `/read`; uploaded images were already public under `/storage`. The middleware is placed before `SubstituteBindings` in the priority list so guests get a login redirect, not a 404 that would reveal which tags or entries exist.
- **Any page render needs a Vite build** (`npm run build`) or a running `npm run dev`; without one the root view throws "Vite manifest not found".

## REST API

All under `/api/v1`, JSON, authenticated with `Authorization: Bearer <token>` except `POST /tokens`.

| Method and path | Purpose |
| --- | --- |
| `POST /tokens` | `email`, `password`, `device_name` → `{token}` (rate limited) |
| `DELETE /tokens/current` | Revoke the token in use |
| `GET /user` | The token's user |
| `GET /entries` | Newest first, 20 per page, cursor in `links.next`; filters `q`, `tag` (slug), `from`, `to` (`YYYY-MM-DD`), `tz` |
| `POST /entries` | `title`, `body` (HTML, sanitized), `tags` (names), `occurred_at` (optional, defaults to now) |
| `GET /entries/{id}` | One entry |
| `PUT /entries/{id}` | `title`, `body`, `tags`; `occurred_at` is rejected |
| `DELETE /entries/{id}` | Soft delete |
| `POST /entries/{id}/restore` | Undo a delete |
| `GET /search?q=` | Same as `/entries` but `q` is required; results include `highlight` segments |
| `GET /tags` | Tags in use, with live `entries_count` |
| `POST /attachments` | Multipart `file` (PNG, JPEG, GIF, WebP, ≤10 MB) → `{id, url}`; put the `url` in an entry body's `<img src>` |

```sh
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/tokens -H 'Accept: application/json' \
  -d email=test@example.com -d password=password -d device_name=cli | jq -r .token)
curl -s -H "Authorization: Bearer $TOKEN" 'http://localhost:8000/api/v1/entries?q=nginx' | jq '.data[].title'
```

## MCP server (Claude Desktop)

`laravel/mcp` exposes a local stdio MCP server registered in `routes/ai.php` as `techlog`. It has four tools: `search-entries` (the timeline filters, capped at 50 results), `get-entry`, `list-tags` (all read-only), and `create-entry`. There is deliberately no update or delete tool. The tools are thin wrappers over `TimelineQuery` and `SaveEntry`; `create-entry` validates with `StoreEntryRequest`'s rules, so sanitizing, tags and search indexing behave exactly as on the other surfaces. Plain-text bodies (`format: "text"`, the default) are converted with `EntryHtml::fromText()`.

stdio has no login, so the server acts as the user named by `TECHLOG_MCP_USER`. Anyone who can run `artisan` on the host can already read the database, so this adds no new access; leave the variable unset to make the server read-only. Because production runs `php artisan optimize`, rerun it after changing either `TECHLOG_MCP_*` variable.

Claude Desktop starts the server itself. Add it to `claude_desktop_config.json` (Settings → Developer → Edit Config) and restart Desktop:

```json
{
  "mcpServers": {
    "techlog": {
      "command": "ssh",
      "args": ["-T", "user@techlog-host", "php /var/web/tech-log/artisan mcp:start techlog"]
    }
  }
}
```

On the same machine as the app, use `"command": "php"` with `"args": ["/var/web/tech-log/artisan", "mcp:start", "techlog"]` instead; don't SSH to the host from itself. For SSH from another machine, that machine's host key must already be in `known_hosts` (run `ssh user@techlog-host true` once) and its public key in the host's `~/.ssh/authorized_keys` (`ssh-copy-id`), with no passphrase prompt, because Desktop can't answer either question. The command must run as a user who can write `database/` (see the permissions above).

Try it without Desktop with `php artisan mcp:inspector techlog`, or by piping JSON-RPC lines in, which exits when the input ends:

```sh
printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"check","version":"1"}}}' '{"jsonrpc":"2.0","method":"notifications/initialized"}' '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | php artisan mcp:start techlog
```

Running `php artisan mcp:start techlog` on its own in a terminal is expected to look broken: it waits for input until you press Ctrl+D (or Ctrl+C), and every line you type that isn't JSON-RPC, including a bare Enter, prints `-32700 Parse error: Invalid JSON`. Tests are in `tests/Feature/Mcp`.

## Conventions

- One feature branch per task, off `master`, merged by fast-forward.
- Commit messages contain no tool attribution.
- Tests first where practical; run the suite and Pint before calling work done.
