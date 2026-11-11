# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Tech Log

Past mistakes and how to prevent them are logged in `MISTAKES.md`, a local-only file that is git-ignored and not on GitHub. If it exists, read it before starting work and append to it when something breaks or gets corrected.

Self-hosted IT change journal: a chronological log of config changes, installs, policy changes, and staff changes. Built to be maintained by a successor, so prefer standard, well-documented choices over clever ones.

## Requirements

Hard:

- Self-hosted
- Standard database
- Tagging
- Image support
- WYSIWYG editor
- Full-text search
- Chronological timeline view
- No commercial components
- Maintainable by a successor
- Scaffolding for both the Vue web frontend and the REST API from the start

Nice to have:

- Multiple authors

## Stack

- Single Laravel app at the repo root (no `/frontend` + `/backend` split)
- Laravel 13 with Inertia.js and Vue 3: controllers return `Inertia::render()` pages with props, pages live in `resources/js/pages` (lowercase, kebab-case names such as `auth/login`), built by Laravel's Vite plugin. No client-side router and no Pinia unless genuinely shared client state appears
- Bootstrap 5 for styling, dark theme
- SQLite with FTS5 virtual table for search
- TipTap for the WYSIWYG editor
- Session auth on the `web` guard, with Laravel Fortify providing the login, logout, and password-reset backend; the screens are our own Inertia pages in Bootstrap. The Laravel Vue starter kit is not used because it ships Tailwind, shadcn-vue, and TypeScript. `FortifyServiceProvider` maps each Fortify screen to its Inertia page. Public registration and passkeys are switched off in `config/fortify.php`; users are created with `php artisan user:create` (the seeder's test user exists only in local and testing). Two-factor auth is on
- Sanctum token auth for the REST API (`php artisan install:api`); the app's own pages don't use it
- Local disk image storage, S3-compatible driver optional later
- Pest for tests
- Pint for PHP linting; ESLint (flat config, `eslint.config.js`) with `eslint-plugin-vue` for `resources/js`

## Data Model

- `entries`: id, user_id, title, body (HTML), body_text (plain, for search), occurred_at, timestamps, soft deletes
- `tags`: id, name, slug
- `entry_tag`: pivot
- `attachments`: id, entry_id, path, mime, size
- `users`: standard Laravel, with a role column to support multiple authors later

## UX Rules

- No full-page reloads: navigate with Inertia `<Link>` and visits; load slow data as deferred props and show skeletons through the `<Deferred>` component
- Single-column, wide, dark-mode timeline as the primary view
- Filter by tag and date range; search results appear inline in the timeline
- Quick-add entry form reachable from every screen
- Keyboard-first: shortcut to create an entry, shortcut to focus search

## Routing and API

- App pages are Inertia routes in `routes/web.php`; data reaches Vue as page props, not via fetch calls
- Timeline uses cursor pagination, appended on scroll with Inertia's merge props; search and tag/date filters are query-string partial reloads of the timeline page
- Editor image uploads are the one JSON endpoint the pages call directly (TipTap needs a URL back), on the `web` guard
- REST API for external clients in `routes/api.php`: versioned under `/api/v1`, resources entries, tags, attachments, search, cursor pagination, Sanctum tokens
- Two thin controller sets (`App\Http\Controllers` for Inertia, `App\Http\Controllers\Api\V1` for REST) share one set of action classes in `app/Actions`, so tag syncing, HTML sanitizing, and `body_text` extraction exist exactly once. Any behaviour change goes in the action and gets tests on both surfaces
- Validate with Form Requests shared by both; shape REST output with API Resources and keep Inertia props equally explicit (no raw models passed to pages)

## Conventions

- A new feature branch off `master` for every task; never commit directly to `master`. Branches are merged by the close-out flow (`/cotb`), which fast-forwards them into `master`
- Commit messages contain no AI attribution
- Write tests first where practical and verify by running them before declaring work done
- Strip HTML from `body` into `body_text` on save and index that column in an FTS5 table kept in sync by triggers
- Sanitize all HTML from the editor on the server before storing
- Keep dependencies minimal and avoid any FOSS package inactive for more than 12 months
- `README.md` covers how to work on the app (setup, deployment, tests, architecture) for the successor; `PROJECT.md` covers what it is and where it stands. Update whichever one a change makes stale in that same change

## Commands

- `composer install && npm install`
- `php artisan migrate --seed`
- `composer dev` to run the API and Vite together
- `php artisan test` for the full suite
- `php artisan test tests/Feature/EntryTest.php` for one file; `php artisan test --filter="creates an entry"` for one test
- `vendor/bin/pint` to lint and format PHP (`vendor/bin/pint --test` checks without writing)
- `npm run lint` to lint JS and Vue with ESLint (`npm run lint -- --fix` to apply fixes)
- `npm test` for the Vitest suite; `npx vitest run <file>` or `npx vitest run -t "<name>"` for one file or test
- `npm run build`

## First Tasks

- Scaffold Laravel 13 with Inertia + Vue, Fortify, Bootstrap, Pest, ESLint, and the API layer (`install:api` for Sanctum and `routes/api.php`, with an authenticated `/api/v1` route group)
- Build login, logout, and password reset Inertia pages in Bootstrap against Fortify, plus API token issuing
- Create migrations, models, and factories for the data model above
- Build entries CRUD as shared actions, exposed through Inertia controllers and `/api/v1` controllers, with tag syncing and FTS5 search
- Build the timeline page and TipTap editor
- Add image upload endpoint and editor integration, plus the attachments API resource
