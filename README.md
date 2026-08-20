# ZBIF InnovaMatch (Raw PHP / Shared LAMP)

Year-round innovation marketplace for the Zimbabwe Business Innovation Forum, organised by ZB Financial Holdings. Theme: Connecting Industry Challenges to Local Innovation for Competitive Growth.

Built for **shared/cPanel LAMP** hosting: PHP + MySQL only. No React, npm, Docker, Redis, or Python required.

## Stack

- PHP 8.1+ (8.0 minimum), Apache `mod_rewrite`
- MySQL 8 / MariaDB 10.4+
- Server-rendered views, Poppins + CSS design tokens
- Vanilla JS (Nova chat, deal-room polling, countdown)
- Cron-driven job queue (email/SMS reminders)
- In-PHP LLM failover (Claude → Groq → Gemini) with form/rule-based degradation

## Quick start (local XAMPP / WAMP / Laragon)

1. Create database `zbif`.
2. Copy `.env.example` to `.env` and set DB credentials and `APP_URL`.
3. Point the web root to `/public` (or use `php -S localhost:8080 -t public`).
4. Import schema and seed:

```bash
mysql -u root -p zbif < database/schema.sql
php database/seeds/seed.php
```

5. Open `APP_URL` in a browser.

### cPanel deploy

1. Upload the project above `public_html`, then set the domain document root to `/public`, **or** copy contents of `public/` into `public_html` and adjust paths in `.env` (`APP_BASE_PATH` if needed).
2. Ensure `storage/` is writable.
3. Import `database/schema.sql`, configure `.env`, run `php database/seeds/seed.php` via SSH or cron once.
4. Add cron:
   - `*/5 * * * * php /path/to/zbif/cron/worker.php`
   - `*/15 * * * * php /path/to/zbif/cron/reminders.php`

## Seed logins

Password for all demo users: `Password123!`

| Email | Role |
|---|---|
| super@zbif.test | Super Admin |
| organizer@zbif.test | Organizer |
| committee@zbif.test | Technical Committee |
| corporate@zbif.test | Corporate |
| innovator@zbif.test | Innovator |
| university@zbif.test | University |
| researcher@zbif.test | Researcher |
| hub@zbif.test | Innovation Hub |
| mentor@zbif.test | Mentor |
| investor@zbif.test | Investor |
| exhibitor@zbif.test | Exhibitor |
| gov@zbif.test | Government |
| student@zbif.test | Student |
| attendee@zbif.test | Attendee |

## Architecture

See `/docs` for data dictionary, RBAC, AI contract, OpenAPI, and demo script.

```
Browser → public/index.php → Router → Controllers → Domain/Ai/Notify → MySQL
Cron → Jobs\Worker → email / Africa's Talking SMS
```

## Changelog

- **Phase 1–7:** Initial raw PHP marketplace (auth, RBAC, challenges, deals, surveys, impact, Nova).
- **Polish pack:** Self-hosted Poppins, photographic hero, premium public layouts, multi-step registration wizard, QR badge image, email verify + magic draft links, dietary/avatar fields, standalone solutions + innovator matches, deal-room files/meetings/agreement, registration approvals + bulk, users/roles, feature flag toggles, CMS page/news editor, comms center, impact PDF print, survey CSV, privacy export/delete, i18n helper (`lang/en.php`), programme filters + ICS.
- **Enterprise A:** Embedding-aware matchmaking (local hashed vectors + optional Gemini), auto-recompute hooks, `cron/embeddings.php`.
- **Enterprise B:** Full survey builder (9 question types), conditional branching, multi-channel delivery, public/venue QR take links, rich analytics (NPS, distributions, word frequency, role segments).
- **Enterprise C:** Real binary PDF exports (`SimplePdf`) for Impact and survey reports (no Composer libs).
- **Enterprise D:** CMS testimonials + foresight insights, interactive foresight filters, homepage social proof.
- **Enterprise E:** File cache for home/impact, meeting “today” + reschedule, awards rubric scoring, enterprise migration script.
- **Enterprise F:** Deal Room depth (meaningful titles, NDA, checklist, stage timeline, gated file download, message notifications, upload validation, admin force-stage + CSV).
- **Enterprise G:** Preference-aware `Notifier::notifyUser`, HTML email template, daily digest cron/worker, survey reminder expansion, mark-read notifications.
- **Enterprise H:** Exhibitor lead CRM (stages/tags/follow-ups), admin booth assign, public exhibitor profiles + floor coords.
- **Enterprise I:** Investor pipeline stages, intro requests, solution picker notes, one-pager PDF.
- **Enterprise J:** Dynamic sitemap, innovator detail pages + JSON-LD, foresight PDF lead magnet, inquiries inbox, rate limits on public forms.
- **Enterprise K:** Tiered sponsorship applications (logo/budget), promote-to-sponsors on approval, sponsor portal assets + entitlements.
- **Enterprise L:** Multi-event CRUD, session event switcher, catalogue clone, impact by edition, registration bound to active event.
- **Enterprise M:** PayNow gateway initiate/result + payment transactions, registration fee path, security headers, system payments/failed-jobs panel.

### Enterprise upgrade for existing DBs

```bash
php database/migrate_enterprise.php
php database/migrate_enterprise2.php
php database/migrate_enterprise3.php
```

Optional cron addition:

- `0 */6 * * * php /path/to/zbif/cron/embeddings.php`
- `15 7 * * * php /path/to/zbif/cron/digest.php`

## Tests

```bash
php tests/run.php
php tests/ai_eval.php
```
