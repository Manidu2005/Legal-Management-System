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
- `recordedLedgerEntries()` → hasMany `LedgerEntry` (FK: `recorded_by`)

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
| case_type | string | nullable | See `LegalCase::CASE_TYPES` |
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
- `courtDates()` → hasMany `CourtDate` (FK: `case_id`)
- `documents()` → hasMany `Document` (FK: `case_id`)
- `ledgerEntries()` → hasMany `LedgerEntry` (FK: `case_id`)

**Helper methods:**
- `trialDateCount()` → count of `court_dates` where `type = 'trial_date'`
- `totalAppearanceFee()` → `trialDateCount() × assignedAttorney.flat_appearance_rate`
- `display_name` accessor → returns `name` when set, otherwise `"{client.name} — {case_type}"`

**Document rename gap:** The documents resource excludes `edit`/`update` routes (`routes/documents.php`). Document names can only be set at upload time (single-file uploads). Post-upload rename is not yet implemented.

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
LegalCase::CASE_TYPES            // ['Civil Litigation', 'Property Dispute', 'Criminal Defence', 'Family Law', 'Labour Dispute', 'Land Acquisition', 'Other']

// Document constraints
Document::ALLOWED_FILE_TYPES     // ['pdf', 'jpg', 'png']
Document::MAX_FILE_SIZE_KB       // 25600
Document::CATEGORIES             // ['evidence', 'deeds', 'correspondence']

// Ledger types
LedgerEntry::TYPES               // ['trust', 'operational']
```
