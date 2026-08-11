---
name: deploy-to-live
description: Deploy changes from the Darasa360 sandbox branch/server to the live (main) Finance and/or Academics servers. Use whenever the user asks to "deploy to live", "push sandbox to live", "go live with this change", or similar for the darasa360-v3 monorepo.
---

# Deploy sandbox → live (Darasa360)

Full checklist for shipping a change from `sandbox` to `main`/live. Read `CLAUDE.md` at the repo root first if you haven't already this session — it has facts this skill assumes you know (SSH port, MyISAM gotcha, etc).

Ask the user which app(s) are affected (Finance, Academics, or both) and what changed (code only? migrations? frontend assets? new npm/composer deps?) so you only do the steps that apply — but always do step 0 and step 6 regardless.

**If SSH or HTTPS to `vda6000.is.cc` times out mid-deploy**: this is a real, recurring whole-host outage on this host, confirmed repeatedly across sessions — not something your deploy caused. Check both (`ssh ... "echo ALIVE"` and `curl -sk https://finance.darasa360.co.tz/`) — if both hang/timeout together, it's the host, not you. Wait and retry (a `Monitor` with an until-loop polling every 10-15s works well for this) rather than assuming a regression or trying to work around it. Your commits are already safe in git either way; the deploy just resumes once the host is back.

## Step 0 — Check for sandbox-server-only changes (do this every time, no exceptions)

This codebase has a history of user-facing changes being made directly on the sandbox server's filesystem and never committed. Before merging anything, check for drift between the sandbox server and git:

```bash
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/finance-sandbox.darasa360.co.tz/public_html/finance && git status --short 2>&1; echo '---'; cd ~/domains/academics-sandbox.darasa360.co.tz/public_html/academics && git status --short 2>&1"
```

If either shows modified/untracked files, that's real design/behavior that live will NOT get from a clean clone unless you pull those exact files down and commit them (see the login-page and vendor-asset incidents in `CLAUDE.md` for what this looked like in practice — `scp` the file down, review it, `git add`, commit with a clear message explaining it was previously sandbox-only).

If sandbox itself isn't a git working tree at that path (i.e., it's just files, no `.git`), instead spot-check anything the user mentions changing recently by diffing the specific file(s) against what's in the local repo clone.

## Step 1 — Commit & merge

```bash
cd "C:/DISK D/darasa360-v3"
git add <files>
git commit -m "..."
git push origin sandbox
git checkout main
git merge sandbox --ff-only   # if this isn't a clean fast-forward, stop and figure out why main has diverged before forcing anything
git push origin main
git checkout sandbox
```

Confirm with the user before pushing/merging unless they've already given blanket approval for the session.

## Step 2 — Pull on the live server(s)

```bash
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/finance.darasa360.co.tz/public_html && git pull origin main"
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/academics.darasa360.co.tz/public_html && git pull origin main"
```

## Step 3 — Dependencies (only if composer.json / package.json changed)

**Composer** — safe to run directly on the server:
```bash
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/<app-domain>/public_html/<app> && composer install --no-dev --optimize-autoloader"
```
If this is the very first deploy to a fresh clone, `bootstrap/cache` and `storage/framework/{cache/data,sessions,views}`, `storage/logs`, `storage/app/public` won't exist yet (git doesn't track empty dirs) — create them first or `package:discover` will fail:
```bash
mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public
chmod -R 775 bootstrap/cache storage
```

**npm — do NOT run on the server.** It's unreliable there (CloudLinux/LVE process limits cause `EAGAIN` from esbuild). Build locally instead:
```bash
cd "C:/DISK D/darasa360-v3/<app>"
npm install
npm run build
scp -P 22 -r "./public/build" olamtecc@vda6000.is.cc:~/domains/<app-domain>/public_html/<app>/public/
```
For Academics specifically: if the build is missing chunks referencing `resources/assets/vendor/...` (e.g. `page-auth.scss` not found in manifest), that's the known missing-vendor-source gap — the source template files were never committed to git. Don't try to fix by rebuilding harder; instead copy the already-working compiled build from `academics-sandbox`'s server (which still has an old working `public/build/` even though the source is gone):
```bash
ssh -p 22 olamtecc@vda6000.is.cc "cp -r ~/domains/academics-sandbox.darasa360.co.tz/public_html/academics/public/build ~/domains/academics.darasa360.co.tz/public_html/academics/public/build"
```
This is a stopgap, not a fix — see `debug-darasa` and the pending follow-up noted in Claude memory about properly re-sourcing the vendor template.

## Step 4 — Migrations (only if new migration files were added)

```bash
cd ~/domains/finance.darasa360.co.tz/public_html/finance
php artisan migrate --path=database/migrations/platform --force   # platform first, Finance owns it
php artisan migrate --force

cd ~/domains/academics.darasa360.co.tz/public_html/academics
php artisan migrate --force
```

If a migration fails with `max key length is 1000 bytes` → MyISAM engine issue, see `CLAUDE.md`; check `'engine' => env('DB_ENGINE', 'InnoDB')` hasn't been removed from `config/database.php`.

If a migration fails with `Access denied ... to database 'olamtecc_mivumoni'` (or any placeholder-looking tenant DB name) → a migration is unconditionally calling `Schema::connection('tenant')->hasTable(...)` without checking the tenant DB is actually reachable. This is fine/expected on any environment with zero schools onboarded. Wrap the tenant-DB portion in a try/catch (see `2026_01_10_143826_add_enhanced_fields_to_report_cards_and_related_tables.php` for the pattern already used) rather than skipping the migration or force-creating a dummy DB.

If a migration partially succeeds before failing (check `SHOW TABLES` on the target DB, and check whether the tracking `migrations` table — which usually lives on the *default* connection's database, not necessarily the DB the migration actually wrote to — recorded it), drop the partially-created table before retrying rather than trying to patch it in place.

**Tenant-table migrations (bare `database/migrations/`, no `central`/`platform` in the path) must NOT be run via a bare `php artisan migrate --force`.** Confirmed real, costly bug (2026-08-11): a bare migrate from the CLI has no active HTTP request to switch the `tenant` connection, so it silently targets whatever the `tenant` connection's *default* database is — on this environment, that's the central DB, which is a real, queryable database (not an error-throwing placeholder), so it can succeed at creating tables in entirely the wrong place with no obvious signal anything went wrong. Always use the tinker-script pattern for any already-provisioned real school:
```bash
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/finance.darasa360.co.tz/public_html/finance && cat > /tmp/migrate_tenant.php << 'PHPEOF'
<?php
use App\\Models\\Central\\School;
use App\\Services\\TenantDatabaseManager;

\$school = School::find(<school_id>);
app(TenantDatabaseManager::class)->switchToSchool(\$school);
Artisan::call('migrate', ['--database' => 'tenant', '--path' => 'database/migrations', '--force' => true]);
echo Artisan::output();
PHPEOF
php artisan tinker --execute=\"require '/tmp/migrate_tenant.php';\" && rm -f /tmp/migrate_tenant.php"
```
Run this **per real school** — there is no bulk "run against every provisioned school" command in Finance (see CLAUDE.md's Pending section). If you're not sure whether a migration file lives under `central/`/`platform/` (safe for the plain `--path=database/migrations/central --force` form above) or bare (needs the tinker pattern), check its actual path before running anything.

**Re-running a tenant migration you just edited (not a new file)**: Laravel tracks migrations by filename, so if you fix the *content* of an already-applied migration, it won't re-run on its own. Delete its tracked row from the tenant DB's own `migrations` table first (via the same tinker pattern, `DB::connection('tenant')->table('migrations')->where('migration', '<name>')->delete()`), then re-run. Only safe to do this for migrations that are genuinely idempotent (checks `Schema::hasTable`/`hasColumn`/existence before acting) — confirmed this exact flow twice on 2026-08-11 fixing a migration that had silently missed several tables on its first pass.

## Step 5 — Cache & permissions (only if this is a fresh deploy, or views/config changed)

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force   # fresh deploy only — never re-run on an already-live app, it invalidates existing sessions/encrypted data
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you only changed a blade view, `php artisan view:clear && php artisan view:cache` is enough — no need to redo everything above.

## Step 6 — Verify (always do this, no exceptions)

```bash
curl -sk https://finance.darasa360.co.tz/ -D - -o /dev/null --max-time 15
curl -sk https://academics.darasa360.co.tz/ -D - -o /dev/null --max-time 15
```
Expect 200/302, not 403/500. `-k` is fine for this check even if there's a transient cert mismatch — that's a separate DirectAdmin AutoSSL concern, not a deploy failure.

If either 500s, immediately tail the log rather than guessing:
```bash
ssh -p 22 olamtecc@vda6000.is.cc "tail -100 ~/domains/<app-domain>/public_html/<app>/storage/logs/laravel.log"
```
and see the `debug-darasa` skill for known error signatures.

Also spot-check whatever the actual change was end-to-end (log in, load the specific page that changed) — a 200 on `/` doesn't prove a specific feature works.

## Step 7 — Log anything new (mandatory if you hit a problem not already covered above)

If any step above needed a workaround that isn't already documented in this file, `CLAUDE.md`, or `debug-darasa`, add it before considering the deploy finished — edit the relevant file, commit on `sandbox`, fast-forward-merge to `main`, push both. A deploy that required improvising isn't actually done until the improvisation is captured for next time.
