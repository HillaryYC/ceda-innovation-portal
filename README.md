# CEDA Innovation Portal

Hackathon source handoff · 23 November 2026

The portal supports an application from draft through submission, evaluation, adjudication, reporting, and publication. Applicants use a public website and applicant workspace. Staff use programme-scoped workspaces. The live demonstration is at [ohprime-ceda-portal.up.railway.app](https://ohprime-ceda-portal.up.railway.app).

## Source layout

| Path | Purpose |
| --- | --- |
| `frontend/src/` | React and TypeScript pages, components, API clients, and tests |
| `frontend/public/` | Public images and branding |
| `backend/app/` | Laravel controllers, models, services, jobs, middleware, and commands |
| `backend/routes/` | Session-based JSON API and web routes |
| `backend/database/` | Schema migrations and seeders |
| `backend/tests/` | Laravel feature tests |
| `backend/config/` | Application and integration configuration |
| `Dockerfile`, `Caddyfile`, `railway.json` | Production build and Railway deployment |
| `docs/` | Hackathon technical brief, user manual, and demo script |

The root `README.md` describes the current connected React/Laravel application. Older Laravel-only README files in the development workspace describe an earlier prototype and are omitted from the source archive.

## Requirements

- PHP 8.3 or newer, Composer 2, and PHP extensions required by Laravel, including PDO SQLite for local use or PDO PostgreSQL for production.
- Node.js 22 and npm.
- SQLite for a local demonstration, or PostgreSQL for a production-like environment.

## Local setup

Run these commands from the extracted source root. PowerShell commands are shown; use equivalent copy/touch commands on other systems.

```powershell
Copy-Item backend/.env.example backend/.env
New-Item backend/database/database.sqlite -ItemType File -Force | Out-Null
composer install --working-dir=backend
Push-Location backend
php artisan key:generate
php artisan migrate
Pop-Location
npm ci --prefix frontend
Copy-Item frontend/.env.example frontend/.env.local
```

`frontend/.env.example` enables the connected portal. In one terminal, run `cd backend` and then `php artisan serve --host=127.0.0.1 --port=8000`. In a second terminal, run `npm --prefix frontend run dev` from the source root. Open `http://127.0.0.1:5173`. Vite proxies `/api` requests to Laravel on port 8000. Do not copy a production `.env` into the source package.

The database starts without a real administrator. To create the single master administrator in a fresh database:

```powershell
Push-Location backend
php artisan ceda:bootstrap-master admin@example.com --name="Master Administrator"
Pop-Location
```

The command prints a one-time activation URL, valid for seven days. Open it locally, choose a password, and complete onboarding. Keep this URL private. The database permits only one master account. For a demonstration using fictional data, consult `tools/seed-preview.php` in the development workspace; preview tools and credentials are intentionally omitted from this handoff.

## Tests and build

```powershell
Push-Location backend
php artisan test
Pop-Location
npm --prefix frontend test
npm --prefix frontend run build
```

The backend tests use an in-memory SQLite database. The frontend build runs TypeScript checks before Vite compiles assets. Generated dependencies and build output are excluded from the archive.

## Main workflows

1. An applicant registers, saves a category-specific draft, uploads supporting files, and submits it.
2. Programme staff manage forms, category routing, people, review templates, and assignments.
3. Evaluators assess assigned applications and may request additional information.
4. Adjudicators review evidence and record a decision and rationale.
5. Reports summarize application and outcome data. Authorized staff may publish awarded ideas.

Published form and review template versions remain attached to the applications and reviews that used them. Server-side role and programme checks enforce access. Uploads are private. The guidance assistant answers from approved material; optional AI summaries require provider configuration and reviewer inspection.

## Configuration

Use `backend/.env.example` as the local template. Use Railway service variables or another secret manager in production. Never include `.env`, database files, private activation links, provider keys, or real applicant records in a handoff.

| Setting | Purpose |
| --- | --- |
| `APP_KEY`, `APP_URL`, `APP_ENV`, `APP_DEBUG` | Laravel encryption, URL, and runtime mode |
| `DB_CONNECTION` and database connection values | SQLite locally; PostgreSQL in production |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Session, cache, and background jobs |
| `MAIL_*` | Invitation and recovery email delivery |
| `PORTAL_SMS_ENABLED`, `PORTAL_SMS_*` | Optional applicant SMS challenge and delivery |
| `PORTAL_AI_API_KEY`, `PORTAL_AI_MODEL` | Optional AI summary service |
| `PORTAL_CHATWOOT_*`, `VITE_CHATWOOT_WEBSITE_TOKEN` | Optional Chatwoot integration; browser token is build-time public configuration |
| `PORTAL_MASTER_MFA_ENABLED`, `PORTAL_REVIEWER_MFA_ENABLED` | Authenticator requirements; both default to `true` |

**Hackathon deployment setting:** production currently sets both MFA switches to `false`, so the master account and evaluator/adjudicator-only accounts use password sign-in. Other staff roles retain MFA. Production also sets `PORTAL_SMS_ENABLED=false` for applicant password sign-in. Re-enable the MFA switches after the shared-access demonstration. These values are deployment settings, not credentials embedded in this source.

## Production deployment

`Dockerfile` builds the React frontend and copies it into Laravel's public directory. `railway.json` defines the Docker build, migration step, and `/up` health check. Configure `APP_KEY`, `APP_URL`, PostgreSQL connection, secure sessions, and any delivery providers before accepting real data. The web container runs migrations on startup. Run a separate queue worker (`php artisan queue:work database --tries=3 --timeout=60`) and scheduler (`php artisan schedule:work`) against the same database and app key for background delivery and allocation. See `docs/TECHNICAL-BRIEF.md` for architecture and limitations.

Do not run demo seeders against a production database. Back up PostgreSQL and test restore, delivery, and staff onboarding before real operations. The browser service has no native executable.

## Current limitations

Live SMTP/SMS delivery, queue and scheduler health, backup/restore, AI provider output, and consultant chat require separate operational verification. The Chatwoot integration is dormant. CEDA must approve final eligibility wording, scoring rules, and privacy/retention processes before real programme use. Automated tests and a healthy deployment do not establish a service-level guarantee.

## Handoff documents

- `docs/TECHNICAL-BRIEF.md`: architecture, security, verification, and limits.
- `docs/USER-MANUAL.md`: role-based user instructions.
- `docs/DEMO-SCRIPT.md`: suggested walkthrough.

## Rebuild the source archive

Run `./PACKAGE-SOURCE.ps1` from the project root. It writes `submission-pack/CEDA-PORTAL-SOURCE.zip` from the source and handoff documents, excluding local secrets, databases, dependencies, logs, and generated assets. Review the ZIP before sharing it. Send access credentials and personal submission documents separately through a private channel.
