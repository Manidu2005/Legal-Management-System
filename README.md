# LexLanka — Legal Practice Management System

LexLanka is a web application for Sri Lankan law firms. It keeps **cases, clients, documents, court dates, billing, and legal research** in one place, with role-based access so partners, associates, and clerks each see only what they are allowed to handle.

This repository is a Laravel application (PHP 8.3, Laravel 13) with a Blade + Tailwind CSS + Alpine.js interface. The schema, Gates, and naming rules that the code follows live in [`CONTRACT.md`](CONTRACT.md).

---

## What the system does

A typical matter moves through LexLanka like this:

1. A **client** is taken in (name, NIC, phone, email, optional photo).
2. A **case** is opened against that client, assigned to an attorney, classified with a Sri Lankan case-type taxonomy, and linked to a court/forum.
3. Staff attach **documents**, log **court dates**, record **research notes**, track **time**, and assign **follow-up tasks**.
4. Lawyers search the firm **judgment library** (uploaded PDFs and public Sri Lankan datasets), attach relevant authorities to the case, and optionally ask a Gemini-backed research assistant grounded in that library.
5. **Billing** keeps client trust money separate from operational fees. Appearance fees are calculated from trial dates × the attorney’s flat rate.
6. When a case hits a milestone (`trial_scheduled`, `judgment_delivered`, `case_closed`), the **client is notified**. Attorneys get a **48-hour reminder** before a trial date.

---

## Features

### Cases and clients

- Client intake with unique NIC, contact details, and profile photo.
- Cases belong to one client and one assigned attorney.
- Statuses: `pending` → `active` → `trial_scheduled` → `judgment_delivered` → `case_closed`.
- Optional **personal-law system** on family/property/succession matters: General Law, Kandyan Law, Thesawalamai, or Muslim Law.
- Case detail page is tabbed: court dates, documents, research, time, tasks, related judgments, and (for financial roles) billing.
- Export a **case brief PDF**.

### Case categories and courts (partner admin)

Partners maintain two lookup lists used on every new case:

- **Case categories** — a 3-level Sri Lankan taxonomy (main type → group → specific type). A case always points at a **leaf** (level 3) type. Inactive types stay on existing cases but are hidden from new-case forms.
- **Courts** — forums from the Supreme Court down to mediation boards and regulatory commissions, grouped by tier.

Demo data is seeded from `CaseCategorySeeder` and `CourtSeeder`.

### Documents and drafting

- Upload PDF / JPG / PNG (max 25 MB) into `evidence`, `deeds`, or `correspondence`.
- Preview, download, and rename (uploader or partner).
- Generate standard PDFs from the case file:
  - Proxy
  - Affidavit
  - Deed of Transfer
  - Letter of Demand
  - Motion to Fix Trial Date
  - Replication / Rejoinder
- Optional **AI draft assistant** (`documents.generate-draft`) to pre-fill generated documents. Direct download still works without AI.

### Scheduling

- Calling dates and trial dates on a firm calendar.
- Associates only see dates for cases assigned to them.
- **Add to Calendar** via `.ics` export (one event or the whole visible calendar).
- Trial dates drive appearance-fee billing and the 48-hour attorney reminder.

### Billing and ledgers

Trust and operational money never mix.

| Ledger | Purpose |
|--------|---------|
| **Trust** | Client money held by the firm (retainers must go here) |
| **Operational** | Fees and firm income (appearance fees, filing fees, posted time) |

- Partners see firm-wide billing; associates see only their assigned cases plus an appearance-income summary.
- Partners also get a **Firm Income** breakdown by attorney.
- Invoices and client financial reports as PDF.
- Recording a retainer as operational is rejected: *“Retainer funds must be recorded in the Client Trust Ledger.”*
- Appearance fee = number of **trial dates** × the assigned attorney’s `flat_appearance_rate`. Calling dates do not count.

### Time and tasks

- Log billable/non-billable minutes against a case. Duration displays as `1h 35m`.
- Partners/associates can **post a time entry to the operational ledger**.
- Case tasks (`pending` / `in_progress` / `done`) with due dates and an assignee. The dashboard shows “My open tasks”. Overdue = past due date and not done.

### Research notes and related judgments

On a case:

- Staff add **research notes** (judgment, act/ordinance, or other) with citation and why it matters.
- **Semantic search** over the judgment library (Gemini embeddings, cosine similarity in PHP — top 10). Attach a hit to the case with a relevance note.
- **Research Q&A** asks a question against the library; Gemini answers using retrieved judgments only (not a public web search). History is stored on the case.

### Judgment library (firm-wide)

Shared reference library, not owned by a single case. Any logged-in role can browse it.

**Upload a PDF**

1. Gemini detects whether the file is one judgment or a multi-case compilation (table of contents preferred).
2. Compilations go to a **review** screen. Staff confirm which detected cases to index.
3. Each confirmed range is sliced, text-extracted, summarized, and embedded (`gemini-embedding-2`, 768-d). Duplicate page ranges of the same PDF are skipped.
4. If Gemini hits daily quota, the batch **pauses**; **Resume** continues after quota resets. **Cancel** keeps already-indexed judgments.

**Public datasets** (partner)

- [navodPeiris/sri-lanka-case-law](https://huggingface.co/datasets/navodPeiris/sri-lanka-case-law) (CLR / CLW / NLR / SLR summaries)
- [nuuuwan](https://github.com/nuuuwan/lk_datasets) Court of Appeal and Supreme Court docs

Import and embed via the UI or:

```bash
php artisan judgments:import-datasets
php artisan judgments:embed-datasets
```

Rows are keyed by `external_id` so re-imports do not duplicate. Filter the library by keyword, category, court, date range, and cited act. Ask a question of a single judgment (`judgments.ask`). In-library **citation links** (`cites` / `followed` / `distinguished` / `overruled`) are stored when Gemini returns cited cases — this is not a national overrule database.

Gemini needs `GEMINI_API_KEY`. Optional `GEMINI_API_KEYS` are extra keys rotated only after a **daily** quota is exhausted (per-minute limits wait on the same key). Extra keys only add capacity if they belong to **separate Google Cloud projects**.

### Search, calculators, locale

- Global search across clients, cases, documents, and judgments.
- **Stamp duty** calculator (indicative Western Province rates: sale, gift, lease, mortgage, affidavit).
- **Inheritance** calculator: General Law (MRIO ss.22–26) or a simplified Muslim-law share; Kandyan / Thesawalamai are not modelled.
- UI language: English, Sinhala (`si`), Tamil (`ta`). The choice is stored on the user and applied by `SetLocale` middleware. Dark mode is a client-side toggle.

---

## Who can do what

Three roles. Suspended users cannot log in.

| | Partner | Associate | Clerk |
|---|---|---|---|
| View cases | All | Assigned to them only | Only if the case has an access code **and** they entered it this session |
| Edit cases | Yes | Assigned cases | No (even after verifying a code) |
| Documents / research / tasks / time | Yes | Assigned cases | Same view rules as cases |
| Billing, invoices, post time to ledger | Yes | Own cases | No |
| Firm income | Yes | No | No |
| User accounts | Yes | No | No |
| Case categories & courts | Yes | No | No |
| Dataset import into the judgment library | Yes | No | No |
| Judgment library browse / upload | Yes | Yes | Yes |

**Case access codes** are optional. The raw code is bcrypt-hashed (`access_code_hash`) and never stored in the clear. Partners, or the assigned associate, set the code on the case edit page. A clerk hitting a protected case or document is redirected to enter the code (5 attempts per minute). After a correct entry, that case stays open for the rest of the session.

Gates in `AppServiceProvider`:

- `view-financials` — partner and associate
- `manage-users` — partner
- `manage-taxonomy` — partner

Associates never see another associate’s cases on the dashboard, calendar, or billing screens.

---

## Notifications

| Event | Who | Channel |
|-------|-----|---------|
| Case status becomes `trial_scheduled`, `judgment_delivered`, or `case_closed` | Client | SMS always; email too if the client has an address |
| Trial date within 48 hours (`lexlanka:send-trial-reminders`, hourly) | Assigned attorney | Email + SMS |

SMS goes through `App\Contracts\SmsGatewayInterface`:

- `SMS_GATEWAY=log` (default) — writes to the log; safe for local use
- `SMS_GATEWAY=twilio` — Twilio REST API (`TWILIO_SID`, `TWILIO_TOKEN`, `TWILIO_FROM`). Missing credentials fall back to a warning log instead of crashing.

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Backend | PHP 8.3, Laravel 13 |
| Auth | Laravel Breeze |
| Database | MySQL (`lexlanka` by default) |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite 8 |
| PDFs | DomPDF (reports / generated docs), FPDI + PDF Parser (judgment page slicing / text extract) |
| AI | Google Gemini (embeddings, summaries, compilation detection, Q&A) |
| Tests | PHPUnit 12 Feature + Unit tests |

---

## Getting started

### Requirements

- PHP 8.3+ with extensions Laravel expects (`pdo_mysql`, `mbstring`, `openssl`, …)
- Composer
- Node.js 18+
- MySQL 8 (XAMPP is fine: host `127.0.0.1`, port `3306`)

### Install

```bash
git clone https://github.com/Manidu2005/Legal-Management-System.git
cd Legal-Management-System

composer install
copy .env.example .env          # Windows
# cp .env.example .env          # macOS / Linux

php artisan key:generate
```

In `.env`, set at least:

```env
APP_NAME=LexLanka
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lexlanka
DB_USERNAME=root
DB_PASSWORD=
```

Create the empty `lexlanka` database, then:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). For Vite hot reload plus the queue and log tailer:

```bash
composer run dev
```

Keep the queue worker running if you dispatch notifications or long judgment imports.

### Optional: Gemini and SMS

```env
GEMINI_API_KEY=
GEMINI_API_KEYS=

SMS_GATEWAY=log
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```

Without a Gemini key, case/document/billing flows still work; judgment ingestion, similarity search, research Q&A, and AI drafts will not.

### Scheduled reminders

The hourly trial-reminder command is registered in `routes/console.php`. In production, run the scheduler:

```bash
php artisan schedule:work
# or a cron entry: * * * * * php /path/to/artisan schedule:run
```

---

## Demo accounts

Seeded by `DatabaseSeeder` (password for all: `password`):

| Role | Email | Notes |
|------|-------|--------|
| Partner | `partner@lexlanka.lk` | Full access — Ranil Jayasuriya, Colombo Fort |
| Associate | `associate@lexlanka.lk` | Dilani Perera |
| Associate | `associate2@lexlanka.lk` | Kasun Wijesinghe, Kandy |
| Clerk | `clerk@lexlanka.lk` | Nuwan Fernando |
| Suspended clerk | `suspended@lexlanka.lk` | Cannot log in |

The first demo case has clerk access code **`247100`**. The second demo case has no code, so a clerk is denied outright.

---

## Project map

```
app/
  Console/Commands/     Trial reminders; dataset import & embed
  Http/Controllers/     Thin HTTP layer (one controller per module)
  Http/Requests/        Validation + authorize()
  Models/               Eloquent models and constants
  Policies/             CaseAccessPolicy
  Services/             Billing, Gemini, PDF split, SMS, calculators, ICS
  Notifications/        Milestone SMS/mail; trial reminders
  Observers/            LegalCaseObserver (status → client notify)
database/migrations/    Schema
database/seeders/       Demo users, cases, taxonomy, courts
resources/views/        Blade UI (layouts, modules, pdf/)
routes/                 One file per module, required from web.php
tests/Feature|Unit/     HTTP authorization matrix + unit calculators
CONTRACT.md             Schema, Gates, and coding contract
```

Route files: `cases`, `documents`, `scheduling`, `billing`, `research`, `time-entries`, `case-tasks`, `judgments`, `users`, `taxonomy`, `calculators`, `auth`.

---

## Tests

```bash
composer test
```

That clears config cache and runs `php artisan test`. Feature tests hit real HTTP routes with `RefreshDatabase` and check the three-role authorization matrix (owned vs not-owned). Gemini calls are faked — tests never call the live API.

---

## Further reading

- [`CONTRACT.md`](CONTRACT.md) — tables, columns, Gates, policies, route naming
- [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) — remaining / sequenced feature work
- [`DOCUMENTATION_DEVELOPMENT_IMPLEMENTATION.md`](DOCUMENTATION_DEVELOPMENT_IMPLEMENTATION.md) — testing approach and user-guide material
