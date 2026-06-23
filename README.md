# Darasa360 v3 — Unified Platform

Monorepo for the Darasa360 school management platform by **Olam Technologies / Helion Tracking**.

```
darasa360-v3/
  finance/     ← Darasa Finance  (Laravel 12, fees/invoices/payroll)
  academics/   ← Darasa Academics (Laravel 11, classes/exams/attendance)
  .github/     ← CI + deploy workflows
```

Both apps share a central **`darasa_platform`** MySQL database for school registry,
student identity, cross-system SSO, and super-admin management.
Finance owns all platform DB migrations; Academics connects but never migrates it.

---

## Branch Strategy

```
feature/*  →  sandbox  →  main
```

| Branch    | Environment | Purpose                                   |
|-----------|-------------|-------------------------------------------|
| `feature/*` | local     | Development; one branch per feature/fix   |
| `sandbox` | Sandbox server | Testing with real school data before go-live |
| `main`    | Production  | Live — schools actively using the system  |

**Rule:** nothing goes to `main` without first passing on `sandbox`.

---

## Checks Before Merging to `sandbox`

These run automatically via GitHub Actions on every PR/push to `sandbox`:

- **PHP 8.2 syntax check** — `php -l` on every file in `app/`, `routes/`, `config/`, `database/`
- **`composer validate`** — catches `composer.json` corruption
- **`php artisan route:list`** — confirms all controllers/middleware resolve without errors
- **PHPUnit** — runs `php artisan test` (failures are non-blocking while suite is sparse)

Manual checks to do before opening a sandbox PR:
- [ ] `php artisan migrate --dry-run` on a local clone — no unexpected table drops
- [ ] No `.env` values committed (check `git diff --name-only` for `.env`)
- [ ] No `dd()`, `dump()`, or `var_dump()` left in code
- [ ] Feature tested locally end-to-end (login, the changed flow, logout)

---

## Checks Before Merging `sandbox` → `main`

### Automated (CI must be green)
- [ ] CI passes green on the sandbox branch — PHP syntax, route:list, composer validate

### Finance portal smoke-tests
- [ ] **Super-admin** — log in, create/toggle a school, view platform student registry
- [ ] **Accountant** — log in, open student list, record a payment, generate invoice PDF
- [ ] **Headmaster** (Finance read-only) — log in, view ledgers, download a report
- [ ] **Parent** — log in, view child fees and invoice

### Academics portal smoke-tests
- [ ] **Platform super-admin** — log in to Academics `/superadmin`, view schools list
- [ ] **Olam admin / system admin** — log in, confirm school list and settings visible
- [ ] **School owner** — log in to owner dashboard, view classes and staff
- [ ] **Headmaster** — log in, view dashboard stats, student list, upcoming events
- [ ] **Academic teacher** — log in, open marks entry for a subject, save marks
- [ ] **Class teacher** — log in, open attendance sheet, mark attendance
- [ ] **Teacher** (general) — log in, confirm correct dashboard and subject access
- [ ] **School accountant** (Academics) — log in, view finance summary if visible
- [ ] **School admin / staff** — log in to school_admin portal, manage settings
- [ ] **Student** — log in, view report card, timetable, attendance record
- [ ] **Parent** (Academics) — log in, view child's attendance and marks

### Cross-system SSO
- [ ] Finance headmaster → "Open Academics" button appears, click → lands in Academics headmaster dashboard without re-login
- [ ] Academics headmaster → "Open Finance" button appears, click → lands in Finance headmaster portal without re-login
- [ ] Button is **hidden** when `cross_jump_enabled = false` for the school
- [ ] Button is **hidden** for a user with no grant in `platform_cross_access`
- [ ] Direct portal login (without handoff) still requires credentials after SSO jump session

### Data integrity
- [ ] CSV student import: 10+ students imported, reg numbers generated, visible in both Finance and Academics
- [ ] One school provisioned end-to-end: classes synced, students synced to both systems
- [ ] Delete a test school → code freed, tenant DBs dropped, platform rows purged

### Deployment config
- [ ] `APP_DEBUG=false` in production `.env` for both apps
- [ ] `php artisan config:cache && route:cache && view:cache` run for both apps after deploy
- [ ] Platform migrations run cleanly: `php artisan migrate --path=database/migrations/platform --force`
- [ ] Academics did NOT run platform migrations (Finance owns them)
- [ ] No `.env` file committed (`git show HEAD --name-only | grep '.env'` returns nothing)

### Sign-off
- [ ] Jackson tested and approved on sandbox before merging to main

---

## First-Time Server Setup

### 1. Create databases (run once on both sandbox and production MySQL)
```sql
CREATE DATABASE darasa_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE darasa_central   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Tenant DBs are created automatically by SchoolProvisioningService
```

### 2. Clone the repo
```bash
git clone https://github.com/olam-devs/darasa360-v3.git /var/www/darasa360
cd /var/www/darasa360
git checkout sandbox   # or main for production
```

### 3. Set up Finance
```bash
cd finance
composer install --no-dev --optimize-autoloader
cp .env.sandbox.example .env    # (or .env.production.example for prod)
# Edit .env — fill in DB credentials, APP_KEY, ACADEMICS_APP_URL
php artisan key:generate
php artisan migrate --path=database/migrations/platform   # platform DB (run FIRST)
php artisan migrate --path=database/migrations/central    # central DB
php artisan db:seed --class=PlatformSuperAdminSeeder      # creates first super-admin
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 4. Set up Academics
```bash
cd ../academics
composer install --no-dev --optimize-autoloader
cp .env.sandbox.example .env    # (or .env.production.example)
# Edit .env — fill in DB credentials, APP_KEY, FINANCE_APP_URL
php artisan key:generate
# DO NOT run platform migrations here — Finance owns them
php artisan migrate   # academics own tenant tables only
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 5. Nginx (two server blocks, one checkout)
```nginx
server {
    server_name finance-sandbox.darasa360.com;
    root /var/www/darasa360/finance/public;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { fastcgi_pass unix:/run/php/php8.2-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
}

server {
    server_name academics-sandbox.darasa360.com;
    root /var/www/darasa360/academics/public;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { fastcgi_pass unix:/run/php/php8.2-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
}
```

---

## GitHub Secrets Required (for auto-deploy)

Set these under **Settings → Environments** (`sandbox` and `production`):

| Secret | Description |
|--------|-------------|
| `DEPLOY_HOST` | Server IP or hostname |
| `DEPLOY_USER` | SSH user (e.g. `deploy`) |
| `DEPLOY_SSH_KEY` | Private SSH key (server's `~/.ssh/authorized_keys` must have the public key) |
| `DEPLOY_PORT` | SSH port (default 22) |
| `DEPLOY_PATH_SANDBOX` | Abs path on sandbox server e.g. `/var/www/darasa360-sandbox` |
| `DEPLOY_PATH_PROD` | Abs path on prod server e.g. `/var/www/darasa360` |

---

## Everyday Development Flow

```bash
# Start a new feature
git checkout sandbox
git pull origin sandbox
git checkout -b feature/my-feature

# ... make changes ...

git add finance/app/... academics/...
git commit -m "feat: describe what you did"
git push origin feature/my-feature

# Open PR: feature/my-feature → sandbox
# CI runs, review, merge
# Test on sandbox server
# Open PR: sandbox → main
# Sign-off, merge → auto-deploys to production
```
