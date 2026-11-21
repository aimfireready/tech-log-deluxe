# Tech Log: project status

How to work on the code is in [README.md](README.md); agent instructions are in [CLAUDE.md](CLAUDE.md); incident history is kept locally in a git-ignored `MISTAKES.md`.

## Product

A Self-Hosted Tech Journal: a chronological, searchable, tagged log of config changes, installs, policy changes and staff changes, written in a rich-text editor with images. It is built so a successor can pick it up and maintain it.

## Surfaces

The web app (Inertia + Vue) is the primary surface. The `/api/v1` REST API is secondary, for scripts and external tools. A local MCP server (`php artisan mcp:start techlog`) lets Claude Desktop search, read and add entries. `/read` is an alternative, read-only reading view styled like a Ghost blog, for browsing the journal as articles; it links back to the main app for any change. Anyone with the `/read?key=…` share link can browse it without an account.

## Business model

Internal tool; no commercial model.

- Shipped on `master`: login and password reset, the timeline with search and tag/date filters, the quick-add editor with image uploads, entry editing and soft delete with Undo, the full `/api/v1`, the MCP server, and the `/read` reading view with its whole-journal share key.
- Designed only, for a possible later public version:
  - **Separate view and create permissions.** Some people could read the log without being able to add to or change it. Today any signed-in user can do everything; the `users.role` column is reserved for this.
  - **Secure sharing of a single entry**, e.g. a link that opens just that entry for someone without an account. Only the all-or-nothing reading-view key (`TECHLOG_READ_KEY`) exists today.

## Hosting and access model

The MVP runs on a LAN only, so access control is not a concern for it: the existing login is kept as-is, plus an optional read-only share key for `/read`, but nothing beyond it (per-user permissions, sharing, hardening for the open internet) is planned for the MVP. A future version may be hosted on a public web server. That would need the two "designed only" items above, a decision on whether the reading-view share key stays (anyone holding the link reads everything), plus HTTPS with `SESSION_SECURE_COOKIE=true` and a review of what the API exposes.

## Environments

| Environment | Where | Notes |
| --- | --- | --- |
| Local | `composer dev` or `php artisan serve` on port 8000 | SQLite file at `database/database.sqlite`; mail goes to the log |
| Production (MVP) | A self-managed host on the LAN, pulling from the public GitHub repo `aimfireready/tech-log-deluxe` (`master`) | Not exposed to the internet. Steps in README "Deploying to a host". Never run `db:seed` there expecting a login; create one with `user:create`. Needs `npm run build` on every deploy, writable `storage/` and `database/`, `APP_DEBUG=false`, and a real mailer for password resets |
| Docker (optional) | `Dockerfile` at the repo root: one Apache + PHP 8.4 container, data in a `storage` volume | Not used by production yet, and the image has never been built (no Docker on the dev machine when it was written). Steps in README "Running with Docker"; needs `APP_KEY`, and `TRUSTED_PROXIES` behind a TLS proxy |

## Task tracking

No tracker or PRD yet; the requirements and the "First Tasks" list in CLAUDE.md serve as both. Work happens one feature branch per task.

## Recent focus

- Backend scaffold (branch `feature/scaffold-backend`): Laravel 13, Pest, Fortify (web auth, registration and passkeys off, 2FA on), Inertia server side, Sanctum with `/api/v1` token issue, revoke and current-user endpoints.
- Frontend scaffold (branch `feature/frontend-scaffold`, stacked on the backend branch): Vue 3 + Inertia client, Bootstrap dark theme, ESLint, Vitest, app shell with search box and user menu, `/` and `?` shortcuts, and all Fortify auth pages.
- Entries and timeline (branch `feature/entries-timeline`): data model with FTS5 search, shared SaveEntry/TimelineQuery actions behind both the Inertia pages and the full `/api/v1` (entries, search, tags, attachments), timeline with day headings, infinite scroll, highlighted inline search, tag and date filters with chips, quick-add panel (`n`) with TipTap and image paste/drop/upload, entry page with editing, soft delete with Undo, and `j`/`k`/`Enter`/`o` keyboard navigation.

- MCP server (branch `feature/mcp-server`): `laravel/mcp` stdio server with `search-entries`, `get-entry`, `list-tags` and `create-entry` tools over the shared actions, acting as the user in `TECHLOG_MCP_USER` (set on production); Claude Desktop on the production host runs it with local `php`, other machines over SSH.
- Imported the 942-entry Google Form log (2020–2026) into production on 2026-11-18 as a one-off script; entries have no tags.
- Reading view (branch `feature/ghost-reader`): Ghost/Casper-style `/read` with a post-card home feed, search, tag archives, serif post pages with reading time, older/newer links and related posts, in light and dark; linked from the user menu.
- Reading view share key (branch `feature/reader-share-key`): `TECHLOG_READ_KEY` lets `/read?key=…` open the reading view without a login; changing the key revokes every old link.
- Docker image (branch `feature/dockerfile`): multi-stage `Dockerfile` (Node build, `php:8.4-apache` runtime) with an entrypoint that prepares the storage volume, migrates and caches; plus `TRUSTED_PROXIES` support via `config/trustedproxy.php`.

## Known gaps

No task tracker or ids yet.

- No view-only access and no single-entry sharing; both are needed only if the app moves to public hosting (see "Hosting and access model").
- No screen for turning on two-factor auth or managing the profile; the challenge page exists but nobody can enrol yet.
- Uploaded images that are never used in an entry, or later removed from one, stay on disk; nothing prunes them.
- No trash screen: deleted entries can only be restored from the Undo toast, the API, or the database.
- No edit history; `updated_at` records only the last change.
- Search results are in timeline order, not ranked by relevance.
- The reading view has no author pages, no RSS feed, and no light/dark toggle (it follows the OS). Most imported entries have no tags or images, so their cards show neither.
- The MCP server can't edit or delete entries, and every MCP write is credited to the one `TECHLOG_MCP_USER`, not to the person using Claude.
- The Docker image is untested: it has not been built or run anywhere yet.
- Tags must contain at least one Latin letter or digit (slugs come from `Str::slug`), so tags written only in other scripts are rejected.
