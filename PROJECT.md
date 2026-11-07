# Tech Log: project status

How to work on the code is in [README.md](README.md); agent instructions are in [CLAUDE.md](CLAUDE.md); incident history is in [MISTAKES.md](MISTAKES.md) once it exists.

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
| Production | Not chosen yet | Self-hosted. Needs `npm run build` on deploy, a writable `storage/` and SQLite file, and a real mailer for password resets |

## Task tracking

No tracker or PRD yet; the requirements and the "First Tasks" list in CLAUDE.md serve as both. Work happens one feature branch per task.

## Recent focus

Backend scaffold (branch `feature/scaffold-backend`): Laravel 13, Pest, Fortify (web auth, registration and passkeys off, 2FA on), Inertia server side, Sanctum with `/api/v1` token issue, revoke and current-user endpoints.

## Known gaps

No task ids exist yet; these map to the "First Tasks" list in CLAUDE.md.

- No Vue frontend: Vue, Bootstrap, TipTap and ESLint are not installed, and every page render fails until the frontend is built.
- No auth pages (login, forgot/reset password, two-factor challenge, confirm password) even though the routes exist.
- No data model: entries, tags, attachments and the users `role` column are not migrated.
- No entries CRUD, FTS5 search, timeline, or image upload.
- No production environment chosen.
