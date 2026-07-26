# Darasa360 — Finance + Academics Monorepo

Two Laravel apps sharing a platform DB, deployed to a shared-hosting DirectAdmin/CloudLinux server. Solo-maintained — this file plus the two skills below (`deploy-to-live`, `debug-darasa`) are the persistent knowledge base across sessions. Read this before touching deployment, migrations, or server config.

**Never put real credentials, passwords, or API keys in this file or in the skills below — this repo is committed to git.** Credentials live in Claude's memory (`project_darasa_finance.md`, project type) and in each server's `.env` files, not here.

## Layout
- `finance/` and `academics/` — two independent Laravel apps, each with its own `composer.json`/`package.json`/`.env`
- Shared `platform` DB (schools registry, super admin, SSO tokens) — Finance owns the platform migrations (`finance/database/migrations/platform/`)
- Each app has a `tenant` DB connection for per-school databases, auto-provisioned via the DirectAdmin API (see `DA_*` env vars)
- Academics has a separate per-school migration path (`academics/database/migrations/school/`) applied via `php artisan schools:migrate-all` (see `app/Console/Commands/MigrateAllSchools.php`) — **not** picked up by the normal `php artisan migrate`

## Branches
- `sandbox` — day-to-day development, deployed to `*-sandbox.darasa360.co.tz`
- `main` — production, deployed to `finance.darasa360.co.tz` / `academics.darasa360.co.tz`
- Workflow: commit to `sandbox` → push → `git checkout main && git merge sandbox --ff-only` → push `main` → pull on whichever live server folder(s) changed

## The #1 gotcha in this codebase
**The sandbox server sometimes has direct file edits that were never committed to git.** This has happened twice already: the entire Academics third-party template asset folder (`academics/resources/assets/vendor/`) and a full login-page redesign (`academics/resources/views/content/authentications/auth-login-basic.blade.php`) both existed only on the sandbox server's filesystem, invisible to `git log`/`git diff` on any machine, and were missed on the first live deploy because "it works on sandbox" wasn't actually backed by git.

**Before trusting that a fresh `git clone` + deploy will look/behave like sandbox, diff the sandbox server's actual files against git for anything user-facing** (blade views, public assets, config). `git status` run *on the sandbox server itself* will show these as modified/untracked — that's the tell. See the `deploy-to-live` skill for the exact commands.

## Server facts worth knowing up front
- SSH is on **port 22**. Port 2222 is the DirectAdmin control panel (HTTPS) — connecting SSH there fails at the banner-exchange stage and looks exactly like a firewall block, but isn't.
- This host's MySQL **default_storage_engine is MyISAM**, not InnoDB (unusual). `config/database.php` in both apps explicitly forces `'engine' => env('DB_ENGINE', 'InnoDB')` on every mysql connection — don't remove that, migrations with unique/long indexes will break with "max key length is 1000 bytes".
- `npm install` on this server is unreliable (CloudLinux/LVE process-limit `EAGAIN` errors from esbuild's postinstall spawning too many processes). Build frontend assets locally and upload `public/build/` via scp — don't run `npm run build` on the server itself.
- Git doesn't track empty directories: `bootstrap/cache` and several `storage/framework/*` subdirs won't exist after a fresh clone and must be created manually before `composer install`'s `package:discover` hook will succeed.
- Each domain's docroot must point at `<app>/public`, not the monorepo root — set via DirectAdmin's domain/subdomain table (a plain, user-editable "Docroot" column, not an admin-only setting).

## Open question: per-school tenant DB credentials (unresolved as of 2026-07-26)
Academics' `tenant` connection (`config/database.php`) falls back to `env('DB_USERNAME')`/`env('DB_PASSWORD')` for username/password, and only the `database` name gets dynamically repointed per school (by `InitializeTenantDatabase` middleware on HTTP requests, and now also by `MigrateAllSchools` for CLI runs — see git history). But checking DirectAdmin's actual DB grants for the one real sandbox school (`olamtecc_acad_002_olam_secondary_school`) showed its authorized DB user is `olamtecc_acad0086460` — **not** the shared `DB_USERNAME` value. Querying via `Schema::connection('tenant')` with the shared credentials got "Access denied" for that school's real database.

This wasn't resolved — it needs someone who knows this system's provisioning history to explain how the real running app successfully authenticates per-school (there may be a credential-resolution path this wasn't found, e.g. something per-school-username related that was missed, or `schools.database_url`'s actual format/meaning differs from what `App\Helpers\DatabaseHelper::buildDatabaseUrl()` produces — they didn't match when compared). **Do not assume the shared `DB_USERNAME`/`DB_PASSWORD` works for arbitrary real schools' tenant databases** until this is understood — and don't bulk-run `migrate:schools` against real onboarded schools until it is, since a connection failure there could still leave the migration *tracking* table showing "applied" without the schema change actually landing (tracking uses a separately, correctly-scoped connection; the actual DDL doesn't).

## Credentials & server access
See Claude memory (`project_darasa_finance.md`) for: SSH key setup, DB credentials (sandbox + live), the scoped DirectAdmin API login key used by the apps themselves, and login credentials for each portal. Not duplicated here.
