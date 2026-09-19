# LexLanka — Schema & Convention Contract

> **This file is the single source of truth for the shared database schema, authorization system, and coding conventions.**
> Read this before touching any module code. If you need a column or Gate that isn't here, ask — don't invent one.

---

## Database Schema

### `users` (extends Laravel default)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| name | string | required | |
| email | string | required, unique | |
| email_verified_at | timestamp | nullable | |
| password | string | required, hashed | |
| remember_token | string(100) | nullable | |
| **role** | enum(`partner`, `associate`, `clerk`) | default: `clerk` | Controls all RBAC |
| **locale** | string | default: `en` | User's preferred locale |
| **branch** | string | nullable | Office/branch name |
| **flat_appearance_rate** | decimal(10,2) | default: 0 | LKR per trial date appearance |
| **status** | enum(`active`, `suspended`) | default: `active` | Suspended users cannot log in |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Model:** `App\Models\User`
**Relationships:**
- `assignedCases()` → hasMany `LegalCase` (FK: `assigned_attorney_id`)
- `uploadedDocuments()` → hasMany `Document` (FK: `uploaded_by`)
- `uploadedJudgments()` → hasMany `Judgment` (FK: `uploaded_by`)
- `recordedLedgerEntries()` → hasMany `LedgerEntry` (FK: `recorded_by`)
- `addedResearchNotes()` → hasMany `ResearchNote` (FK: `added_by`)

**Helper methods:** `hasRole(string)`, `isPartner()`, `isActive()`

---

### `clients`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| name | string | required | |
| nic | string | required, **unique** | Sri Lankan National ID |
| phone | string | nullable | |
| email | string | nullable | |
| intake_date | date | required | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Model:** `App\Models\Client`
**Relationships:**
- `cases()` → hasMany `LegalCase`

---

### `legal_cases`

> Table is `legal_cases`, model is `LegalCase` (PHP reserves `Case`).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| client_id | bigint unsigned | FK → `clients.id`, cascade delete | |
| **name** | string | nullable | Case name/title |
| assigned_attorney_id | bigint unsigned | FK → `users.id`, restrict delete | |
| **case_category_id** | bigint unsigned | FK → `case_categories.id` (level 3 leaf), nullable, null on delete | The specific Sri Lankan case type this case is filed under — see `case_categories` below |
| **court_id** | bigint unsigned | FK → `courts.id`, nullable, null on delete | Which court/forum is hearing this case — see `courts` below |
| **applicable_law** | string | nullable | Which personal-law system governs (Family/Property/Succession only) — see `LegalCase::APPLICABLE_LAWS` |
| **case_type_other** | string | nullable | Free-text detail when the leaf category is "Other"; also preserves any legacy free-text `case_type` value from before the taxonomy migration |
| status | enum | default: `pending` | See values below |
| **access_code_hash** | string | nullable | Bcrypt hash of client access code — the raw code is never stored |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status enum values (exact):**
```
pending | active | trial_scheduled | judgment_delivered | case_closed
```

**Milestone statuses** (trigger client SMS notification — FR-4.1):
```
trial_scheduled | judgment_delivered | case_closed
```
Use `LegalCase::MILESTONE_STATUSES` constant in code.

**Model:** `App\Models\LegalCase`
**Relationships:**
- `client()` → belongsTo `Client`
- `assignedAttorney()` → belongsTo `User` (FK: `assigned_attorney_id`)
- `caseCategory()` → belongsTo `CaseCategory` (FK: `case_category_id`)
- `court()` → belongsTo `Court` (FK: `court_id`)
- `courtDates()` → hasMany `CourtDate` (FK: `case_id`)
- `documents()` → hasMany `Document` (FK: `case_id`)
- `ledgerEntries()` → hasMany `LedgerEntry` (FK: `case_id`)
- `researchNotes()` → hasMany `ResearchNote` (FK: `case_id`)
- `judgments()` → belongsToMany `Judgment` via `case_judgment`
- `caseJudgments()` → hasMany `CaseJudgment` (FK: `legal_case_id`)

**Helper methods:**
- `trialDateCount()` → count of `court_dates` where `type = 'trial_date'`
- `totalAppearanceFee()` → `trialDateCount() × assignedAttorney.flat_appearance_rate`
- `display_name` accessor → returns `name` when set, otherwise `"{client.name} — {caseCategory.name or case_type_other}"`

**Document rename gap:** The documents resource excludes `edit`/`update` routes (`routes/documents.php`). Document names can only be set at upload time (single-file uploads). Post-upload rename is not yet implemented.

---

### `case_categories`

> Hierarchical Sri Lankan case-type taxonomy — admin-managed (partner only) via `case-categories.*` routes. A `legal_cases` row always points at a **level 3** (leaf) row via `case_category_id`.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| name | string | required | |
| parent_id | bigint unsigned | FK → `case_categories.id`, nullable, null on delete | Null only for level 1 |
| level | tinyint unsigned | required | `1` = Main Type, `2` = Group, `3` = Specific type (leaf) |
| sort_order | unsigned int | default: `0` | Display order within siblings |
| is_active | boolean | default: `true` | Inactive categories are hidden from new-case forms but existing cases keep the reference |
| description | text | nullable | |
| created_at / updated_at | timestamp | | |

**Model:** `App\Models\CaseCategory`
**Constants:** `LEVEL_MAIN_TYPE` (1), `LEVEL_GROUP` (2), `LEVEL_SPECIFIC_TYPE` (3), `LEVELS`
**Relationships:** `parent()` belongsTo self, `children()` hasMany self, `cases()` hasMany `LegalCase`
**Helper methods:** `isLeaf()`, `fullPath()` (breadcrumb, e.g. `"Civil › Property & Land Law › Partition action"`)
**Seeder:** `Database\Seeders\CaseCategorySeeder` — full 3-level Civil/Criminal/Constitutional & Public Law hierarchy, with a trailing "Other" leaf under every group.
**Routes:** `routes/taxonomy.php` — `case-categories.*` resource (except `show`) + `case-categories.toggle-active`, all `role:partner`.

---

### `courts`

> Sri Lankan forum/court map (Supreme Court down to Mediation Boards and regulatory commissions) — admin-managed (partner only) via `courts.*` routes.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| name | string | required | |
| tier | string | required | See `Court::TIERS` |
| description | text | nullable | |
| sort_order | unsigned int | default: `0` | |
| is_active | boolean | default: `true` | |
| created_at / updated_at | timestamp | | |

**Model:** `App\Models\Court`
**Constant:** `TIERS` (apex, superior_appellate, superior_original, first_instance_civil, first_instance_criminal, localized, personal_law, adr, regulatory)
**Relationships:** `cases()` hasMany `LegalCase`
**Seeder:** `Database\Seeders\CourtSeeder`
**Routes:** `routes/taxonomy.php` — `courts.*` resource (except `show`) + `courts.toggle-active`, all `role:partner`.

---

### `court_dates`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → `legal_cases.id`, cascade delete | |
| date | datetime | required | |
| type | enum(`calling_date`, `trial_date`) | required | `trial_date` drives billing & reminders |
| reminder_sent | boolean | default: `false` | Set to `true` after 48-hr reminder fires |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Model:** `App\Models\CourtDate`
**Relationships:**
- `legalCase()` → belongsTo `LegalCase` (FK: `case_id`)

**Helper methods:** `isTrialDate()`

---

### `documents`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → `legal_cases.id`, cascade delete | |
| **name** | string | nullable | Document name/title |
| file_path | string | required | Storage path |
| file_type | string | required | Extension: pdf, jpg, png |
| category | enum(`evidence`, `deeds`, `correspondence`) | required | |
| uploaded_by | bigint unsigned | FK → `users.id`, restrict delete | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Validation rules (enforce in Form Request, not just UI):**
- Max file size: **25,600 KB** (25 MB) — use `Document::MAX_FILE_SIZE_KB`
- Allowed types: `pdf`, `jpg`, `png` — use `Document::ALLOWED_FILE_TYPES`
- Categories: use `Document::CATEGORIES`

**Model:** `App\Models\Document`
**Relationships:**
- `legalCase()` → belongsTo `LegalCase` (FK: `case_id`)
- `uploader()` → belongsTo `User` (FK: `uploaded_by`)

**Helper methods:**
- `display_name` accessor → returns `name` when set, otherwise the original uploaded filename (`basename(file_path)`)

---

### `ledger_entries`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → `legal_cases.id`, cascade delete | |
| type | enum(`trust`, `operational`) | required | **Never allow cross-type entries** |
| amount | decimal(12,2) | required | |
| description | string | required | |
| recorded_by | bigint unsigned | FK → `users.id`, restrict delete | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Business rule (FR-3.2):** Retainer funds MUST go into `trust`. A request to record a retainer as `operational` must be rejected with: _"Retainer funds must be recorded in the Client Trust Ledger."_

**Model:** `App\Models\LedgerEntry`
**Relationships:**
- `legalCase()` → belongsTo `LegalCase` (FK: `case_id`)
- `recorder()` → belongsTo `User` (FK: `recorded_by`)

---

### `research_notes`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| case_id | bigint unsigned | FK → `legal_cases.id`, cascade delete | |
| category | string | required | See `ResearchNote::CATEGORIES` |
| citation | string | required | Case name/reference, or act name and number |
| court_or_source | string | nullable | |
| note | text | required | Why this reference is relevant to this case |
| source_url | string | nullable | |
| added_by | bigint unsigned | FK → `users.id`, restrict delete | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Model:** `App\Models\ResearchNote`
**Relationships:**
- `legalCase()` → belongsTo `LegalCase` (FK: `case_id`)
- `addedBy()` → belongsTo `User` (FK: `added_by`)

---

### `judgments`

> Firm-wide shared reference library — **not** owned by a single case. Open to any authenticated role; do **not** gate with `CaseAccessPolicy`.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| title | string | nullable | Case name/citation (e.g. "Perera v. Silva"); auto-filled by ingestion if blank on upload |
| category | string | required | See `Judgment::CATEGORIES` |
| court | string | nullable | Auto-filled by ingestion if blank on upload |
| decided_date | date | nullable | |
| pdf_path | string | nullable in practice for dataset rows (empty string) | Storage path (PDF only) for uploads; dataset imports may omit a local file |
| summary | text | nullable | Filled by `JudgmentIngestionService` or public-dataset summaries |
| **cited_acts** | json | nullable | Array of act/ordinance names & sections for this judgment (not buried in summary prose) |
| embedding | json | nullable | 768-d unit vector from Gemini `gemini-embedding-2` text embeddings; filled by ingestion or `judgments:embed-datasets` |
| uploaded_by | bigint unsigned | FK → `users.id`, restrict delete | |
| **source_hash** | string(64) | nullable | SHA-256 of the original uploaded PDF (dedupe / resume fingerprint), or of `external_id` for dataset rows |
| **source_start_page** | unsigned int | nullable | 1-based start page within that source PDF |
| **source_end_page** | unsigned int | nullable | 1-based end page within that source PDF |
| **external_id** | string | nullable, unique | Dataset primary key (`navod:SLR:…` / `nuuuwan:…`) so re-imports never duplicate |
| **source_dataset** | string(64) | nullable | `navod_sri_lanka_case_law`, `nuuuwan_appeal_court`, `nuuuwan_supreme_court` |
| **source_url** | text | nullable | Official PDF or Hugging Face source URL when no local file exists |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Unique:** (`source_hash`, `source_start_page`, `source_end_page`) — prevents indexing the same page-range from the same PDF twice.

**Validation rules (enforce in Form Request):**
- Max file size: **25,600 KB** (25 MB) — use `Judgment::MAX_FILE_SIZE_KB`
- Allowed types: `pdf` only — use `Judgment::ALLOWED_FILE_TYPES`
- Categories: use `Judgment::CATEGORIES`

**Model:** `App\Models\Judgment`
**Relationships:**
- `uploader()` → belongsTo `User` (FK: `uploaded_by`)
- `legalCases()` → belongsToMany `LegalCase` via `case_judgment`

**Ingestion pipeline** (`App\Services\JudgmentIngestionService` + `JudgmentImportProcessor` + helpers):
1. **Detect** multi-judgment compilations via `gemini-flash-latest` structured output (`is_compilation`, `cases[]`) against the full original PDF (Files API for large files). Prefer TOC; leave `cited_acts` empty at detection time.
2. If compilation: create a durable **`judgment_import_batches`** + **`judgment_import_items`** row set (`awaiting_review`). Review UI → confirm. Processor indexes item-by-item; on Gemini **429 quota** the batch is **`paused`** (source PDF kept). User hits **Resume** after quota resets. **Cancel** keeps already-indexed judgments and discards remaining items.
3. Dedup fingerprint: `source_hash` + page range. Re-uploading the same PDF attaches to an open batch (no re-detect) or pre-marks items as `skipped` when those pages already exist — **no duplicate judgments, no wasted Gemini calls** for finished cases.
4. Per judgment PDF (whole file or slice): extract text (`PdfTextExtractor`) — never send raw PDF to embeddings, and summarization now also runs on the extracted text (not the PDF file) to cut token cost and 503s. Embed via `GeminiEmbeddingService` (`title:…\|text:…`, 768-d, chunk+average+renormalize). Summarize with `gemini-flash-latest`, **batched up to `JudgmentIngestionService::SUMMARY_BATCH_SIZE` (5) cases per Gemini call** for compilations — one request for a batch of cases instead of one per case.
5. Query-side: `task: search result | query: …`.
6. Transient `503`/connection failures retry with backoff automatically (not counted as quota).

**Public datasets** (`App\Services\JudgmentDatasetImportService`): partners can import [navodPeiris/sri-lanka-case-law](https://huggingface.co/datasets/navodPeiris/sri-lanka-case-law) (CLR/CLW/NLR/SLR summaries) and [nuuuwan](https://github.com/nuuuwan/lk_datasets) Court of Appeal / Supreme Court Hugging Face docs into `judgments` without re-running PDF detection. Rows are keyed by `external_id`. Gemini `embedDocument` is applied to title+summary+cited acts so `JudgmentSimilaritySearchService` (related-judgment search) keeps using `gemini-embedding-2`. Commands: `php artisan judgments:import-datasets` and `php artisan judgments:embed-datasets`. Hugging Face’s Supreme Court *docs* split is a small sample; Appeal Court docs are the large metadata set. Download falls back to `source_url` when no local PDF exists.

Requires `services.gemini.key` (`GEMINI_API_KEY`). Optional extra fallback keys via `services.gemini.keys` (`GEMINI_API_KEYS`, comma/newline-separated) — `App\Services\GeminiKeyPool` handles every 429 from detection, summarization, and embedding calls: a short/retryable per-minute rate limit is **waited out on the same key first** (respecting Gemini's own `retryDelay`), and only a non-retryable daily-cap error (or a retryable one that still fails after waiting) rotates to the next configured key. This order matters — rotating through every key instantly on any 429 tends to burst-trigger the same per-minute limit on all of them at once, which is slower than just waiting. The pool's "current key" pointer is cached (`gemini_api_key_pool_index`), not per-request.
  - **Retryable vs daily-cap classification** (`App\Exceptions\GeminiQuotaExceededException::fromApiBody`) is based on Gemini's `quotaId` (`...PerMinute...`/`...PerSecond...` = retryable; `...PerDay...` = not), **not** on the `retryDelay` hint alone — Google's own API can attach a short `retryDelay` (e.g. `1s`) even to a fully-exhausted **daily** quota error, which would otherwise cause a pointless wait-then-fail on every key. If no `quotaId` is present (older-format error), it falls back to the retryDelay-based heuristic (`<=120s` ⇒ retryable).
  - A key that hits a non-retryable daily-cap error is cached as **exhausted until the next midnight Pacific time** (`gemini_api_key_pool_exhausted_{index}`), so a later request (e.g. clicking **Resume** again minutes later) skips that key instantly instead of re-dialing and re-waiting on it. If every configured key is already known-exhausted, `run()` fails fast with an aggregated message instead of looping through all of them again.
  - **Important caveat**: Gemini free-tier quotas are enforced **per Google Cloud project, not per API key string**. Extra `GEMINI_API_KEYS` only add real capacity if they belong to separate Google accounts/projects — keys generated under the same account typically share one quota pool, so rotation between them won't help a shared cap.

**Import tables:**
- `judgment_import_batches` — `source_hash`, `source_pdf_path`, `original_filename`, `category`, `status` (`awaiting_review|processing|paused|completed|cancelled`), `pause_reason`, `uploaded_by`
- `judgment_import_items` — per detected case: page range, citation/court/date/`cited_acts`, `include`, `status` (`pending|processing|completed|skipped|failed`), `judgment_id`, unique (`batch_id`, `start_page`, `end_page`)

**Routes:** `routes/judgments.php` — `judgments.index`, `judgments.store`, `judgments.imports.review`, `judgments.imports.confirm`, `judgments.imports.resume`, `judgments.imports.cancel`, `judgments.datasets.import`, `judgments.datasets.embed`, `judgments.download`, `judgments.destroy` (auth only; no CaseAccessPolicy). Dataset import/embed is partner-gated (`manage-taxonomy`).

---

### `case_judgment`

> Pivot attaching firm-wide library judgments to a specific case. Gated by `CaseAccessPolicy::view` on the case (same as documents).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| legal_case_id | bigint unsigned | FK → `legal_cases.id`, cascade delete | |
| judgment_id | bigint unsigned | FK → `judgments.id`, cascade delete | |
| relevance_note | text | nullable | Why this judgment is relevant to the case |
| added_by | bigint unsigned | FK → `users.id`, restrict delete | |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Unique:** (`legal_case_id`, `judgment_id`)

**Model:** `App\Models\CaseJudgment`
**Relationships:**
- `legalCase()` → belongsTo `LegalCase` (FK: `legal_case_id`)
- `judgment()` → belongsTo `Judgment`
- `addedBy()` → belongsTo `User` (FK: `added_by`)

**Similarity search:** `App\Services\JudgmentSimilaritySearchService` — embeds the lawyer’s query with `GeminiEmbeddingService::embedQuery` (`task: search result | query: …`, 768-d), then ranks all judgments with non-null `embedding` by cosine similarity in PHP (top 10). No DB vector index.

**Routes** (in `routes/judgments.php`, all require auth + `CaseAccessPolicy::view` on the case):
- `POST /cases/{case}/related-judgments/search` → `cases.related-judgments.search`
- `POST /cases/{case}/related-judgments` → `cases.related-judgments.store` (attach)
- `DELETE /cases/{case}/related-judgments/{caseJudgment}` → `cases.related-judgments.destroy` (attacher or partner via `manage-users` Gate)

---

## Authorization System

### Middleware: `role`

**Registration:** Aliased in `bootstrap/app.php`
**Class:** `App\Http\Middleware\RoleMiddleware`

**Usage in routes:**
```php
// Single role
Route::middleware('role:partner')->group(function () { ... });

// Multiple roles
Route::middleware('role:partner,associate')->group(function () { ... });
```

Unauthorized users receive HTTP 403 with message: _"You do not have permission to access this resource."_

### Gates

Defined in `App\Providers\AppServiceProvider::boot()`

| Gate Name | Who Can | Use For |
|-----------|---------|---------|
| `view-financials` | `partner` only | Billing dashboard, ledger views, financial reports |
| `manage-users` | `partner` only | User CRUD, account suspension, branch assignment |
| `manage-taxonomy` | `partner` only | Case category taxonomy CRUD, courts/forum list CRUD |

**Usage in controllers:**
```php
Gate::authorize('view-financials');
// or
if (Gate::allows('view-financials')) { ... }
// or
$this->authorize('view-financials');
```

**Usage in Blade views:**
```blade
@can('view-financials')
    {{-- Billing content here --}}
@endcan

@can('manage-users')
    {{-- User management content here --}}
@endcan
```

### Policies

Defined in `App\Policies\` and registered in `App\Providers\AppServiceProvider::boot()` via `Gate::policy()`.

| Policy | Registered For | Abilities |
|--------|-----------------|-----------|
| `CaseAccessPolicy` | `App\Models\LegalCase` | `view`, `manageAccessCode` |

**`CaseAccessPolicy::view(User $user, LegalCase $case)`**

| Role | Rule |
|------|------|
| `partner` | Always `true` — unrestricted access to every case. |
| `associate` | `true` only if `$case->assigned_attorney_id === $user->id`. |
| `clerk` | `true` only if the case has an access code configured **and** the clerk has verified it for this case in the current session. See "Case Access Codes" below. |

**`CaseAccessPolicy::manageAccessCode(User $user, LegalCase $case)`** — governs who may set/change a case's access code.

| Role | Rule |
|------|------|
| `partner` | Always `true`. |
| `associate` | `true` only if assigned to that case. |
| `clerk` | Always `false` — a verified clerk gains `view` rights only, never the ability to manage the code. |

**Usage in controllers:**
```php
$this->authorize('view', $case);
$this->authorize('manageAccessCode', $case);
```

**Usage in Blade views:**
```blade
@can('manageAccessCode', $case)
    {{-- Access code set/change form --}}
@endcan
```

`LegalCaseController` and `DocumentController` both use `App\Http\Controllers\Concerns\EnsuresCaseAccessCode`, which redirects an unverified clerk to the code-entry form instead of surfacing a bare 403 when a case's `view` check would otherwise fail solely due to a missing session verification.

**Important:** `LegalCaseController::edit()` and `update()` additionally hard-block the `clerk` role before ever consulting the policy. Do not rely on `authorize('view', $case)` alone to gate edit/update actions — a code-verified clerk legitimately passes `view`, but must never be allowed to edit or update a case.

### Case Access Codes (Phase 1b)

Each case may have an optional access code, stored only as a bcrypt hash (`legal_cases.access_code_hash`). The raw code is **never** persisted, logged, or serialized (the column is in `LegalCase::$hidden`).

**Model helpers** (`App\Models\LegalCase`):
- `hasAccessCode(): bool` — whether a code is configured.
- `setAccessCode(string $code): void` — hashes and saves a new/changed code.
- `verifyAccessCode(string $code): bool` — checks a raw code against the stored hash.

**Who can set/change a code:** a partner, or the associate assigned to that specific case — enforced by `CaseAccessPolicy::manageAccessCode`. A small form on the case edit view (`resources/views/cases/edit.blade.php`), gated by `@can('manageAccessCode', $case)`, posts to `PATCH /cases/{case}/access-code`.

**Clerk verification flow:**
1. A clerk requesting a case (`cases.show`) or one of its documents (`documents.show`/`download`/`preview`) whose case has a code configured, and which hasn't been verified this session, is redirected to `GET /cases/{case}/access-code`.
2. Submitting the correct code (`POST /cases/{case}/access-code`, throttled `5,1`) sets `session("case_access_verified.{$case->id}", true)` and redirects back into the case (or the originally requested document, if any).
3. For the rest of that session, `CaseAccessPolicy::view()` returns `true` for that clerk/case pair — no need to re-enter the code.
4. If a case has **no** access code configured, a clerk is denied outright (redirected nowhere — there's nothing to verify — and the normal policy check 403s).

**Session keys used:**
- `case_access_verified.{case_id}` — `true` once a clerk has verified the code for that case this session.
- `case_access_intended.{case_id}` — transient, holds the originally requested URL so the clerk lands back where they meant to go after verifying.

### Suspended User Handling

Suspended users are rejected at login (in `LoginRequest::authenticate()`) with message: _"Your account has been suspended. Please contact an administrator."_

---

## Naming Conventions

| What | Convention | Example |
|------|-----------|---------|
| Database columns | `snake_case` | `assigned_attorney_id`, `flat_appearance_rate` |
| Route names | Resource naming | `cases.index`, `cases.store`, `documents.create` |
| Route URIs | Plural kebab-case | `/legal-cases`, `/court-dates`, `/ledger-entries` |
| Controllers | Singular + Controller | `LegalCaseController`, `DocumentController` |
| Form Requests | Action + Model + Request | `StoreDocumentRequest`, `UpdateLedgerEntryRequest` |
| Model constants | UPPER_SNAKE_CASE | `Document::MAX_FILE_SIZE_KB`, `LegalCase::STATUSES` |
| Feature branches | `feature/{module}` | `feature/documents`, `feature/scheduling`, `feature/billing`, `feature/users-rbac` |

---

## Branch Convention

| Branch | Owner | Purpose |
|--------|-------|---------|
| `main` | Integration | Nobody commits directly; merge via PR only |
| `feature/core` | Module 5 | Foundation: schema, auth, shell |
| `feature/documents` | Module 1 | FR-1.1, FR-1.2, FR-5.1 |
| `feature/scheduling` | Module 2 | FR-2.1, FR-2.2, FR-4.1 |
| `feature/billing` | Module 3 | FR-3.1, FR-3.2, FR-4.2 |
| `feature/users-rbac` | Module 4 | FR-5.2, FR-6.1, FR-6.2 |

---

## PDF Generation

**Package:** `barryvdh/laravel-dompdf` (v3.1.2 installed)

```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('pdf.client-report', ['case' => $case]);
return $pdf->download('report.pdf');
```

Place PDF Blade templates in `resources/views/pdf/`.

---

## Key Constants Reference

```php
// Case statuses
LegalCase::STATUSES              // ['pending', 'active', 'trial_scheduled', 'judgment_delivered', 'case_closed']
LegalCase::MILESTONE_STATUSES    // ['trial_scheduled', 'judgment_delivered', 'case_closed']
LegalCase::APPLICABLE_LAWS       // ['general' => 'General Law', 'kandyan' => 'Kandyan Law', 'thesawalamai' => 'Thesawalamai', 'muslim' => 'Muslim Law']

// Case category taxonomy (hierarchical — see `case_categories` table above)
CaseCategory::LEVELS             // [1, 2, 3] — Main Type / Group / Specific type (leaf)

// Courts / forum map (see `courts` table above)
Court::TIERS                     // ['apex' => 'Apex', 'superior_appellate' => ..., ... 'regulatory' => 'Regulatory / Quasi-Judicial']

// Document constraints
Document::ALLOWED_FILE_TYPES     // ['pdf', 'jpg', 'png']
Document::MAX_FILE_SIZE_KB       // 25600
Document::CATEGORIES             // ['evidence', 'deeds', 'correspondence']

// Ledger types
LedgerEntry::TYPES               // ['trust', 'operational']

// Research note categories
ResearchNote::CATEGORIES         // ['judgment', 'act_or_ordinance', 'other']

// Judgment library
Judgment::ALLOWED_FILE_TYPES     // ['pdf']
Judgment::MAX_FILE_SIZE_KB       // 25600
Judgment::CATEGORIES             // ['judgment', 'act_or_ordinance', 'other']
```
