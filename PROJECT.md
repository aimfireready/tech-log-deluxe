# Tech Log: project status

How to work on the code is in [README.md](README.md); agent instructions are in [CLAUDE.md](CLAUDE.md); incident history is kept locally in a git-ignored `MISTAKES.md`.

## Product

A self-hosted IT change journal: a chronological, searchable, tagged log of config changes, installs, policy changes and staff changes, written in a rich-text editor with images. It is built so a successor can pick it up and maintain it.

## Surfaces

The web app (Inertia + Vue) is the primary surface. The `/api/v1` REST API is secondary, for scripts and external tools.

## Business model

Internal tool; no commercial model.

- Shipped: nothing user-facing yet.
- Designed only: everything in the requirements list in CLAUDE.md.

## Environments

| Environment | Where | Notes |
| --- | --- | --- |
| Local | `composer dev` or `php artisan serve` on port 8000 | SQLite file at `database/database.sqlite`; mail goes to the log |
| Production | A self-managed host pulling from the public GitHub repo `aimfireready/tech-log-deluxe` (`master`) | Steps in README "Deploying to a host". Never run `db:seed` there expecting a login; create one with `user:create`. Needs `npm run build` on every deploy, writable `storage/` and `database/`, `APP_DEBUG=false`, and a real mailer for password resets |

## Task tracking

No tracker or PRD yet; the requirements and the "First Tasks" list in CLAUDE.md serve as both. Work happens one feature branch per task.

## Recent focus

- Backend scaffold (branch `feature/scaffold-backend`): Laravel 13, Pest, Fortify (web auth, registration and passkeys off, 2FA on), Inertia server side, Sanctum with `/api/v1` token issue, revoke and current-user endpoints.
- Frontend scaffold (branch `feature/frontend-scaffold`, stacked on the backend branch): Vue 3 + Inertia client, Bootstrap dark theme, ESLint, Vitest, app shell with search box and user menu, `/` and `?` shortcuts, and all Fortify auth pages.
- Entries and timeline (branch `feature/entries-timeline`): data model with FTS5 search, shared SaveEntry/TimelineQuery actions behind both the Inertia pages and the full `/api/v1` (entries, search, tags, attachments), timeline with day headings, infinite scroll, highlighted inline search, tag and date filters with chips, quick-add panel (`n`) with TipTap and image paste/drop/upload, entry page with editing, soft delete with Undo, and `j`/`k`/`Enter`/`o` keyboard navigation.

## Known gaps

No task tracker or ids yet.

- **Login may not be wanted.** A request said "there is no auth"; login was kept pending confirmation. Removing it would also leave the API without a way to issue tokens.
- No screen for turning on two-factor auth or managing the profile; the challenge page exists but nobody can enrol yet.
- Uploaded images that are never used in an entry, or later removed from one, stay on disk; nothing prunes them.
- No trash screen: deleted entries can only be restored from the Undo toast, the API, or the database.
- No edit history; `updated_at` records only the last change.
- Search results are in timeline order, not ranked by relevance.
- Tags must contain at least one Latin letter or digit (slugs come from `Str::slug`), so tags written only in other scripts are rejected.
