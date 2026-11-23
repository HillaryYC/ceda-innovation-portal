# CEDA Innovation Portal — technical submission brief

**23 November 2026 · Hackathon prototype**

## Motivation and research

CEDA's brief asks for a secure, usable route from idea submission through collaborative evaluation, feedback, decision and recognition. The product was shaped by the brief's role, reporting and security criteria, and by direct usability checks on a phone and laptop. An early long form was divided into shorter steps after it proved discouraging to complete. The warm CEDA palette and clearer status labels were retained after review of long staff sessions. These checks are limited usability evidence, not a formal representative study.

## Solution and workflow

The browser portal has public, applicant, evaluator, adjudicator, programme manager and administrator experiences. Applicants register, create category and stage dependent drafts, upload permitted evidence, submit and monitor status. Submitted applications remain read-only. Authorised staff can configure programme forms and scoring templates, assign reviews, request additional information, assess against criteria, and record adjudication decisions. Applicants can respond to information requests. Reports and controlled publication show outcomes. The guidance assistant answers from staff-approved material; it is not live consultant chat.

## Architecture and stack

React and TypeScript provide the responsive browser interface. Laravel/PHP serves the API and enforces permissions and validation on the server. PostgreSQL stores accounts, applications, form versions, criteria, assignments, assessments, decisions and activity records. Railway hosts the web service and database. The Docker build compiles the frontend and serves it through Laravel. Background jobs and scheduled work are designed for invitations, notifications, allocation and expiry; production worker and scheduler services still need verification. Configuration and secrets belong in Railway environment variables, not the source archive.

Forms and scoring criteria use immutable published versions. Existing applications and reviews retain the version they started with, preserving the basis for past decisions. Automatic evaluator matching considers category preference and workload; managers can intervene when no suitable reviewer or template is available. The API is separated by domain concerns, allowing future integrations without rebuilding the whole interface.

## Security and data integrity

Server-side role and programme checks restrict access. Applicants can see their own records; staff see only authorised programme work. Authenticator MFA is supported for staff. For the hackathon deployment, the master and evaluator/adjudicator-only accounts currently use password sign-in through temporary configuration switches; other staff roles retain MFA. Applicant email/password sign-in is active, and applicant SMS challenge is disabled by configuration. Password recovery and delivery channels require configured providers. Uploads are private and file types are checked. Sensitive actions and review events are recorded. Rate limits and non-disclosing sign-in errors reduce account probing. AI summaries, when enabled, omit account records and attachments, mask known identifiers, use `store=false`, and require evaluator review; masking cannot guarantee anonymity in free text. Botswana Data Protection Act compliance requires a further legal and operational review, including retention, privacy notices and provider arrangements.

## Verification and limits

The 23 November release notes record automated frontend and backend tests, successful Railway deployments and HTTP 200 responses for `/` and `/up`. These are build and availability checks, not a 24/7 service-level guarantee or a measured response-time benchmark. Live email/SMS delivery, queue and scheduler health, backup and restore, AI output, and end-to-end consultant chat remain to be confirmed. The Chatwoot integration is dormant because the connected cloud account rejected bot API access. A published guidance assistant is available instead.

## Lessons and next steps

The main design lesson was to make a long application easier to resume and to preserve evaluation history when programme rules change. The main integration lesson was to separate code completion from provider readiness: a feature should only be claimed as live after its credentials, jobs and end-to-end path are verified. Next steps are monitored workers and backups, provider delivery, live consultant chat with opening hours and analytics, cross-browser acceptance checks, performance measurements, and CEDA approval of final form wording and scoring rules.

## Run and deploy

The source is in `frontend/` and `backend/`. Locally, install Node and PHP/Composer dependencies, configure a local `.env` with a database and app key, run Laravel migrations, start the Laravel service, and run `npm --prefix frontend run dev`. Run `npm --prefix frontend test`, `npm --prefix frontend run build`, and `php artisan test` from `backend/`. For deployment, use the supplied Dockerfile and `railway.json`, set production secrets and PostgreSQL connection variables, then run migrations and separately start queue and scheduler services. See the root `README.md` for configuration and setup details. No native executable is needed because the solution is browser based.
