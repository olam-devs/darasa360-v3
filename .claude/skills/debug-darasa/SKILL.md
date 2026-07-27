---
name: debug-darasa
description: Diagnose errors on the Darasa360 Finance/Academics live or sandbox servers (500 errors, migration failures, blank/broken pages, login issues). Use whenever the user reports something broken on finance.darasa360.co.tz, academics.darasa360.co.tz, or their sandbox equivalents.
---

# Debug Darasa360 (Finance / Academics)

Always start from the actual error, not a guess. Read `CLAUDE.md` at the repo root first for the standing gotchas (SSH port, MyISAM, sandbox-drift) — most issues you'll hit are already documented there or below.

**This workflow is not done when the error is fixed — it's done when Step 4 (logging) is also done.** If you diagnose and fix something whose error signature isn't already listed below, adding it is a required part of the task, not a follow-up suggestion. Don't wait to be asked.

## Step 1 — Get the real error

```bash
ssh -p 22 olamtecc@vda6000.is.cc "tail -100 ~/domains/<app-domain>/public_html/<app>/storage/logs/laravel.log"
```
`<app-domain>` is `finance.darasa360.co.tz` or `academics.darasa360.co.tz` (or the `-sandbox` equivalents). `APP_DEBUG=false` in production, so the browser only shows a generic "Server Error" — the real exception is always in this log. Grep for `local.ERROR` or `production.ERROR` and read the *first* exception line plus the `[previous exception]` block if present (the root cause is usually in the previous exception, not the top-level wrapper).

## Known error signatures

**Browser shows "500 Internal Server Error" but `laravel.log` has no matching exception around that timestamp**
Not an application error — a web-server-level execution-time timeout. Happens on Academics' "Create School" (runs 100+ migrations synchronously in-request) and similar long-running admin actions. Check the actual data first (`SELECT` the school/record directly, or reload the relevant list page) before assuming it failed — the underlying work likely completed fine, just after the HTTP response window closed. Don't blindly retry the same action; retrying a school creation that actually succeeded will try to reprovision an already-existing school.

**A newly-created school (Finance or Academics) shows up in the schools list but core features are broken/tables are missing**
This host can kill a very long-running request (e.g. Academics' 100+ per-school migrations, or Finance's cross-system call to Academics) mid-execution, leaving a School row that was created *before* migrations ran but never finished. Check the school's actual tenant database table count directly (`SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=<db>` — should be ~56-57 for Academics) rather than trusting that the row's existence means it's usable. If incomplete: drop the partial database (REST API, not classic), delete the School/User rows, and recreate with the same or a new name. See CLAUDE.md's cross-system provisioning section for the full story and why this is a known, only-partially-mitigated risk.

**`DirectAdmin dropDatabase` / classic API delete returns `error=0` but the database still exists**
The classic `/CMD_API_DATABASES?action=delete` claims success without actually deleting anything on this server (verified 2026-07-26). Both apps' `DirectAdminService::dropDatabase()` already use the working alternative (`DELETE /api/db-manage/databases/{name}` on the REST API) — if you're calling DirectAdmin's delete directly for some one-off cleanup, use that REST endpoint, not the classic one.

**`Unable to locate file in Vite manifest: resources/assets/vendor/...` / `ViteManifestNotFoundException`**
Academics only. The third-party template's vendor scss/js source (`academics/resources/assets/vendor/`) isn't in git — see `CLAUDE.md`. A fresh `npm run build` produces an incomplete manifest missing these chunks. Fix: copy the already-compiled `public/build/` from `academics-sandbox`'s server (still has the old working build) rather than trying to rebuild from source. Longer-term fix is re-sourcing the vendor template and committing it — not yet done as of 2026-07-26 (check Claude memory for current status).

**`SQLSTATE[42000] ... Specified key was too long; max key length is 1000 bytes`**
MyISAM key-length limit, not InnoDB's. This host's `default_storage_engine` is MyISAM. Check `config/database.php` still has `'engine' => env('DB_ENGINE', 'InnoDB')` on the relevant connection — if a new connection array was added without it, that's the bug. If a table already got created as MyISAM before this was caught, `DROP TABLE` it (check it has no real data first) and re-run the migration after the engine fix is in place — `ALTER TABLE ... ENGINE=InnoDB` also works if data must be preserved.

**`SQLSTATE[HY000] [1044] Access denied for user '...'@'localhost' to database 'olamtecc_mivumoni'` (or any similarly placeholder-looking DB name)**
A migration or code path is hitting the `tenant` connection's *default* database, which is just a hardcoded placeholder (real tenant DBs are chosen dynamically per-school at runtime). This is expected to fail on any environment with zero schools onboarded — it means something isn't checking reachability before assuming the tenant DB exists. Wrap the offending `Schema::connection('tenant')->...` calls in a try/catch (see the fix in `academics/database/migrations/2026_01_10_143826_add_enhanced_fields_to_report_cards_and_related_tables.php` for the exact pattern already used once).

**403 Forbidden on the domain root**
Docroot is still pointing at the monorepo root (`public_html/`) instead of `public_html/<app>/public`. Check DirectAdmin's domain/subdomain table — this is a plain user-editable "Docroot" column, no admin access needed.

**SSL cert mismatch / wrong CN when checking via `curl`/`openssl s_client`**
Check with `openssl s_client -connect <domain>:443 -servername <domain> | openssl x509 -noout -subject`. If the subject CN is for a *different* domain, AutoSSL/Let's Encrypt hasn't (yet) issued a cert for this one — trigger it from DirectAdmin's SSL Certificates page. Often self-resolves shortly after a new domain/subdomain is created; don't assume it's broken just because a fresh `curl` check without a browser's cert cache shows it.

**SSH connection closes immediately with `kex_exchange_identification: Connection closed by remote host`**
You're connecting to port 2222 (the DirectAdmin panel, HTTPS) instead of port 22 (actual SSH). Not a firewall block, not a key problem — just the wrong port.

**`npm install` fails with `EAGAIN` from an esbuild/postinstall spawn**
CloudLinux/LVE process-limit throttling on the shared host — npm/esbuild spawn too many child processes. Don't retry-loop this on the server; build locally and `scp` the `public/build/` output instead (see `deploy-to-live` skill).

**Migration table shows no record for a migration that clearly ran (partially)**
The Laravel migration *tracking* table lives on the connection used by `php artisan migrate` (usually the app's default `mysql` connection), which is not necessarily the same database a given migration actually wrote to if that migration explicitly uses `Schema::connection('platform')` or `Schema::connection('tenant')`. Check the actual target database's tables directly, don't assume the tracking table tells the whole story.

## Step 2 — If it's a "looks different on live than sandbox" issue

Don't assume it's a deploy step you missed — first check whether sandbox itself has uncommitted local changes:
```bash
ssh -p 22 olamtecc@vda6000.is.cc "cd ~/domains/<app>-sandbox.darasa360.co.tz/public_html/<app> && git status --short"
```
If sandbox shows modifications git doesn't know about, that's your answer — see `CLAUDE.md`'s "#1 gotcha" and pull the real file down, review, and commit it (don't blindly copy sandbox's live filesystem state to production without reading the diff first — it might include half-finished experiments too).

## Step 3 — Direct DB inspection when the error is DB-shaped

```bash
mysql -u <db_user> -p'<password>' -D <database> -e "SHOW TABLES;"
mysql -u <db_user> -p'<password>' -D <database> -e "SHOW VARIABLES LIKE 'default_storage_engine';"
```
Credentials are in Claude memory (`project_darasa_finance.md`), not duplicated here since this file is committed to git.

## Step 4 — Log it (mandatory if the error signature above didn't already cover it)

Before ending the session/task, if you just diagnosed and fixed something not already listed under "Known error signatures":

1. **Edit this file** — add a new entry under "Known error signatures" with the exact error text (or a distinctive substring of it), what caused it, and the fix. Match the existing format: bold error snippet, then a short paragraph.
2. **Commit it** — `git add .claude/skills/debug-darasa/SKILL.md && git commit -m "docs: add <short description> to debug-darasa"` on `sandbox`, then fast-forward-merge into `main` per the usual workflow (see `deploy-to-live`). This file only helps future sessions if it's actually pushed, not left as a local edit.
3. If the underlying fact is more "standing knowledge" than "error lookup" (e.g. a new server quirk, not a one-off bug), put it in `CLAUDE.md` instead/also.
4. If it's a one-off mistake specific to this conversation rather than a pattern likely to recur, skip logging it — this file is for recurring signatures, not a changelog.

Treat this the same as you'd treat writing a test after fixing a bug: the fix isn't complete until the next occurrence is cheaper to resolve than this one was.
