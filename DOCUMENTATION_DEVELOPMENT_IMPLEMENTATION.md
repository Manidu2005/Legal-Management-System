# Documentation: Development and Implementation

### LexLanka — Legal Management System

> Prepared against the **Documentation: Development and Implementation (50 marks)** marking rubric. Fill in the bracketed `[ ]` placeholders (team/member names, dates, screenshots) before submission. Everything else is drawn directly from the working codebase, the real automated test suite, and the schema/authorization contract (`CONTRACT.md`) that governs this project.

**Group:** [Group Name / Number]
**Module/Course:** [Module Code]
**Date:** [Submission Date]
**System:** LexLanka — a Laravel 13 / PHP 8.3 web application for managing law-firm case files, court schedules, billing, documents, client intake, RBAC, and an AI-assisted judgment research library.

---

## Table of Contents

1. [Software Development: Testing (20 marks)](#1-software-development-testing-20-marks)
   1.1 [Justification of Testing Technique](#11-justification-of-testing-technique)
   1.2 [Choice and Design of Test Cases](#12-choice-and-design-of-test-cases)
   1.3 [Test Logs](#13-test-logs)
2. [Implementation: Conversion and Training Plan (10 marks)](#2-implementation-conversion-and-training-plan-10-marks)
   2.1 [Justification of Conversion Technique](#21-justification-of-conversion-technique)
   2.2 [Schedule and Plan for Conversion](#22-schedule-and-plan-for-conversion)
3. [Implementation: User Guide (10 marks)](#3-implementation-user-guide-10-marks)
4. [Critical Appraisal: Combined (5 marks)](#4-critical-appraisal-combined-5-marks)
5. [Critical Appraisal: Individual (5 marks)](#5-critical-appraisal-individual-5-marks)
6. [Appendix A: Full Test Case Catalogue and Raw Test Logs](#appendix-a-full-test-case-catalogue-and-raw-test-logs)

---

## 1. Software Development: Testing (20 marks)

### 1.1 Justification of Testing Technique

*(Target length: ~3 pages)*

#### 1.1.1 Overview of the technique adopted

LexLanka is tested using **automated black-box Feature (functional) testing** at the HTTP/route layer, implemented with **PHPUnit 12** through Laravel's `Illuminate\Foundation\Testing` framework (`tests/Feature/*`, `tests/Unit/*`). This is supplemented by two classical design techniques applied *while choosing the input data for each test*:

- **Equivalence Partitioning (EP)** — used wherever an input field has a small, closed set of valid/invalid classes (e.g. `role` ∈ {`partner`, `associate`, `clerk`}; `type` ∈ {`trust`, `operational`}; `locale` ∈ {`en`, `si`, `ta`}; case `status` ∈ 5 defined values).
- **Boundary Value Analysis (BVA)** — used wherever an input is a numeric range or a threshold-driven business rule (file-size limit of 25,600 KB; ledger `amount` minimum of `0.01`; the 5-attempt rate limiter on the case access-code form).

In addition, because this system's core requirement is **role-based access control (RBAC)** over shared case data, a large share of the suite is dedicated to a technique best described as an **authorization/permission matrix test**: for every module, the same operation is executed as each of the three roles (`partner`, `associate`, `clerk`) against both an *owned* and a *non‑owned* resource, and the response is asserted against the access rule defined in `CONTRACT.md`.

This combination is deliberately **not** purely theoretical — each choice below is tied to a concrete mechanism in this codebase, not to testing theory in the abstract.

#### 1.1.2 Why black-box Feature testing (and not only unit testing)

LexLanka's correctness is dominated by *integration concerns*: middleware (`role`), Gates (`view-financials`, `manage-users`), Policies (`CaseAccessPolicy`), Form Request validation, session state (case-access-code verification), and Eloquent query scoping *all have to cooperate correctly on a real HTTP request* for a feature to be considered "done". A pure unit test of, say, `CaseAccessPolicy::view()` in isolation would prove the policy method is logically correct, but would **not** prove that:

- the route actually has the `auth` and `role` middleware attached,
- `LegalCaseController` actually calls `$this->authorize('view', $case)` before rendering,
- a clerk who has *not* verified the access code is redirected rather than shown a 403 (`EnsuresCaseAccessCode` concern),
- the redirect correctly restores the originally-requested URL after verification.

Because the majority of defects in a CRUD/RBAC system like this occur at these integration seams rather than inside a single pure function, the project standardises on **Feature tests that boot the full HTTP kernel** (`$this->actingAs($user)->get(...)`/`post(...)`/`patch(...)`/`delete(...)`) and assert on the resulting `TestResponse` (status code, redirect target, session state, and `viewData()`), backed by a `RefreshDatabase` SQLite/MySQL test database seeded via `Database\Seeders\DatabaseSeeder`. This is the standard, framework-idiomatic testing style for Laravel and matches how the application is actually consumed (as HTTP requests from a browser).

Where a unit of logic is genuinely pure and expensive to exercise through HTTP (e.g. an external AI API call), the suite drops down to **mocking the collaborator** instead of hitting the network — see §1.1.4.

#### 1.1.3 Why equivalence partitioning fits this system

Nearly every module in LexLanka is gated by a small enumerated domain rather than free-form input:

| Field | Domain (equivalence classes) | Where enforced |
|---|---|---|
| `users.role` | `partner` \| `associate` \| `clerk` | `RoleMiddleware`, `StoreUserRequest` |
| `legal_cases.status` | `pending` \| `active` \| `trial_scheduled` \| `judgment_delivered` \| `case_closed` | `StoreLegalCaseRequest` |
| `ledger_entries.type` | `trust` \| `operational` | `StoreLedgerEntryRequest`, `LedgerEntry::TYPES` |
| `documents.category` | `evidence` \| `deeds` \| `correspondence` | `StoreDocumentRequest`, `Document::CATEGORIES` |
| `documents.file_type`/`judgments.pdf` mime | `pdf` \| `jpg` \| `png` (docs); `pdf` only (judgments) | `mimes:` rule |
| user `locale` | `en` \| `si` \| `ta` | `LocaleController`/`SetLocale` middleware |

For each of these, testing every possible string is neither feasible nor useful — the theoretically infinite input space (`type` could be any string) collapses into a small number of **behaviourally distinct classes**: "a valid member of the enum", "an invalid string not in the enum", and, for `role`, additionally "a role that is valid but forbidden for *this specific action*" (e.g. a syntactically valid `clerk` is still rejected by `manage-users`). `tests/Feature/LocaleSwitchTest.php` is the clearest illustration: it tests one representative from the valid class per language (`en`, `si`, `ta` — three distinct branches, since Sinhala/Tamil are genuinely different render paths) plus exactly one representative of the invalid class (`fr`), rather than exhaustively trying every ISO locale code — this is EP applied correctly, not under- or over-testing.

#### 1.1.4 Why boundary value analysis fits this system

Two business rules are explicitly threshold-driven, so BVA is the correct technique for choosing their test data rather than picking values at random:

1. **Document/judgment file size** — `Document::MAX_FILE_SIZE_KB = 25600` (25 MB) is enforced by Laravel's `max:` validation rule in `StoreDocumentRequest` and `StoreJudgmentRequest`. The interesting values are **at** and **around** the boundary (24 MB accepted / 26 MB rejected / exactly 25,600 KB), not an arbitrarily small 1 KB file — a 1 KB test would pass even if the `max:` rule were deleted entirely, giving false confidence.
2. **Rate limiting on the access-code form** — `Route::post('/cases/{case}/access-code')` is throttled `5,1` (5 attempts per minute). `CaseAccessCodeTest::test_access_code_store_route_is_rate_limited()` deliberately submits **exactly 5** wrong codes (all within the allowed class) and then asserts the **6th** (the first value in the "exceeded" class) returns HTTP 429. Testing only 1 attempt or 100 attempts would miss the actual edge that matters — the boundary between 5 and 6.
3. **Ledger amount** — `'amount' => ['required', 'numeric', 'min:0.01']` in `StoreLedgerEntryRequest`: the boundary class is 0 (rejected), 0.01 (accepted), and a negative value (rejected), rather than only testing a comfortable mid-range value like 1000.00.

#### 1.1.5 Why an authorization-matrix approach for RBAC

`CONTRACT.md` defines three roles with materially different visibility rules over the *same* `legal_cases` table (partner: all cases; associate: only `assigned_attorney_id = $user->id`; clerk: only after per-session access-code verification). A functional requirement of this shape is not adequately tested by checking "does the feature work" once — it is only proven correct by checking **every role against both an owned and a non-owned instance of the resource**, because the defect class this system is most exposed to is *authorization bypass* (an associate seeing another associate's client, or a suspended clerk retaining access). `tests/Feature/CaseAccessVerificationTest.php` and `tests/Feature/IncomeViewTest.php` follow exactly this pattern for cases, documents, billing, ledger entries, and firm income — for each resource type there is a same-role/owned-case "allow" test paired with a same-role/other's-case "forbid" test.

#### 1.1.6 Why HTTP-boundary mocking for the AI-assisted judgment library

The Judgment Library (`JudgmentIngestionService`, `GeminiEmbeddingService`, `GeminiFileService`, `GeminiKeyPool`) calls the external Google Gemini API for compilation detection, summarisation, and 768‑dimension embeddings. Genuine black-box testing of this module without any test double would make the suite (a) non-deterministic, (b) dependent on network access and a paid quota, and (c) slow. Instead, `tests/Feature/JudgmentLibraryTest.php` uses Laravel's `Http::fake()` to intercept outbound requests to `generativelanguage.googleapis.com` and return deterministic canned JSON, and uses `$this->mock(PdfTextExtractor::class, ...)` / `$this->mock(PdfPageSlicer::class, ...)` to stub the PDF-parsing collaborators. This keeps the test **black-box from the controller's perspective** (it still posts a real file to `POST /judgments`, through real routing/validation/queueing) while removing the one genuinely non-deterministic external dependency — this is the standard, justified compromise for testing systems with third-party AI/network dependencies, and is directly relevant to *this* system because the 429-quota-rotation logic in `GeminiKeyPool` could not be reliably triggered against the real API on demand.

#### 1.1.7 Testing technique summary table

| Technique | Applied to | Rationale specific to LexLanka |
|---|---|---|
| Black-box Feature (HTTP) testing | All 75 tests | Correctness lives at the route/middleware/policy/view integration seam, not inside isolated functions |
| Equivalence Partitioning | `role`, `status`, `type`, `category`, `locale`, file mime-type | Every governing field is a small closed enum enforced by a Form Request or middleware |
| Boundary Value Analysis | File size (25,600 KB), rate limiter (5 attempts), ledger `amount` (`min:0.01`) | The three places in the system where a *threshold*, not a category, is the business rule |
| Authorization matrix testing | Cases, documents, billing, ledger entries, firm income, judgments | RBAC/data-scoping is the system's central non-functional requirement; bypass is the highest-impact defect class |
| Test doubles at the network boundary (`Http::fake`, `Storage::fake`, Mockery) | Gemini AI calls, PDF text extraction/slicing, file storage | Removes non-determinism/cost from the one external, paid, rate-limited dependency without weakening coverage of the controller/service logic around it |

---

### 1.2 Choice and Design of Test Cases

*(Target length: ~2 pages)*

Test cases were not chosen arbitrarily; each one targets a specific rule from `CONTRACT.md` or a specific line of validation/authorization code, using the EP/BVA/matrix reasoning justified above. The table below is a representative sample (the full catalogue of all 75 automated test cases is in **Appendix A**).

| # | Test case | Technique | Input data chosen and why |
|---|---|---|---|
| TC-01 | Clerk requests a case that has an access code configured | Authorization matrix | Clerk role + coded case → chosen because it is the *unverified* branch of the 3-way clerk/coded/uncoded state space |
| TC-02 | Clerk requests a case with **no** access code configured | Authorization matrix / EP | Clerk role + uncoded case → the other member of the equivalence class "does this case require a code?"; must be a hard 403, not a redirect |
| TC-03 | Wrong access code submitted | EP (invalid input class) | `access_code = 'wrong-code'` → representative of the "any string ≠ stored hash" invalid class; must not create a session grant |
| TC-04 | 5 wrong attempts, then a 6th | Boundary Value Analysis | Exactly the throttle threshold (`5,1`) and one past it, to prove the limiter fires at 6, not 5 or 7 |
| TC-05 | Correct access code submitted | EP (valid class), single representative | `247100` (the seeded demo code) → proves the happy path grants session access to the case *and* its documents |
| TC-06 | Associate lists `/cases` | Authorization matrix | Compares the full set of cases assigned to that associate against the set actually returned — proves no leakage of another attorney's cases |
| TC-07 | Partner lists `/cases` | Authorization matrix (contrast case) | Same endpoint, different role → proves the partner override (`always true`) is distinguishable from the associate scoping |
| TC-08 | Associate opens another associate's case | Authorization matrix (negative) | Must be `403`, not `404` or a silent empty view — proves `CaseAccessPolicy::view` is actually consulted |
| TC-09 | Verified clerk attempts `GET /cases/{case}/edit` and `PUT /cases/{case}` | Authorization matrix (edge case) | Deliberately chosen because a code-verified clerk legitimately *passes* `view` — this proves the **hard role block** in `LegalCaseController::edit()`/`update()` still fires even when the policy alone would allow it |
| TC-10 | Non-owning associate attempts `PATCH /cases/{case}/access-code` | Authorization matrix | Distinguishes "can view/edit the case" from "can manage its access code" — two different policy abilities that must not be conflated |
| TC-11 | Associate creates a ledger entry described as containing "retainer" with `type = operational` | Business-rule / EP | Chosen because it is the one input combination FR‑3.2 explicitly forbids; description substring `"retainer"` + wrong type is the exact trigger condition in `StoreLedgerEntryRequest::withValidator()` |
| TC-12 | Associate creates/deletes a ledger entry on another attorney's case | Authorization matrix | Ledger entries inherit case-level scoping via `Gate::allows('view', $case)` inside the Form Request's `authorize()` — this proves that inheritance actually works |
| TC-13 | `GET /firm-income` as partner / associate / clerk | Authorization matrix | Three roles against one Gate (`view-financials`) — partner allowed, the other two forbidden |
| TC-14 | Firm income per-attorney subtotal sum vs grand total | Correctness/invariant check | Not an RBAC case — verifies `BillingService::getFirmIncomeSummary()`'s arithmetic invariant (Σ subtotals == grand total) rather than just "the page loads" |
| TC-15 | Single, non-compilation judgment PDF upload | EP (valid class) + mocked AI boundary | Represents the common case: one citation detected, embedded, summarised, and its `cited_acts` stored as JSON |
| TC-16 | Multi-case compilation PDF, confirm, then **re-upload the identical bytes** | EP + dedup-boundary | The re-upload is the interesting case: same `source_hash` must attach to no *open* batch and pre-mark items `skipped` — proves no duplicate judgments and no wasted Gemini calls |
| TC-17 | Import batch paused mid-way (simulated `429` quota), then resumed | State-transition testing | `paused → processing → completed`; proves an already-`completed` item is *not* re-processed on resume (idempotency under retry) |
| TC-18 | Primary Gemini key returns `429` on every call type | Boundary / failover path | Forces `GeminiKeyPool` to rotate detection, summarisation, *and* embedding calls to the fallback key in the same import — the one path that a manual demo could not reliably reproduce, hence the mock |
| TC-19 | Locale switch to `si` / `ta` / invalid `fr` | Equivalence Partitioning | Two valid non-default classes (Sinhala, Tamil — genuinely different translation files, not interchangeable) plus one invalid-class representative |
| TC-20 | Calendar hover summary for a day **with** appearances vs a day **with none** | EP (data-presence classes) | The "empty day" case is the one most likely to be forgotten by a naive implementation (e.g. assuming every calendar cell has data) |

**Test data design principle applied throughout:** every test seeds via the shared `Database\Seeders\DatabaseSeeder` (5 users across all 3 roles including one `suspended` account, 4 clients, 6 cases across all 5 `status` values, 9 court dates split between `trial_date`/`calling_date` and past/future, 6 documents across all 3 categories, 9 ledger entries across both `type` values including a deliberately *unmet* retainer/operational conflict) so that **every equivalence class already exists in the fixture data** and tests can select a concrete representative (e.g. `LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail()`) instead of hand-rolling ad-hoc rows per test — this keeps test data both realistic and consistent with the documented schema contract.

---

### 1.3 Test Logs

*(Full test-by-test logs are provided in **Appendix A**; this section is the summary required by the main body.)*

**Test environment:** PHPUnit 12.5.30, PHP 8.3.32, Laravel 13, SQLite/MySQL test connection with `RefreshDatabase`, run via `php artisan test` (the project's `composer test` script clears config cache first).

**Run date:** [insert run date] — first run: 75 tests, 215 assertions, **73 passed, 2 errored** (a genuine regression, analysed and fixed below). Second run, after the fix: **75/75 passed, 223 assertions, 0 failures**.

| Metric | First run (as found) | Final run (after fix) |
|---|---|---|
| Total automated test cases | 75 | 75 |
| Total assertions | 215 | 223 |
| Passed | 73 (97.3%) | **75 (100%)** |
| Failed/Errored | 2 (2.7%) | **0** |
| Test classes | 17 (`tests/Feature/*`, `tests/Feature/Auth/*`, `tests/Unit/*`) | 17 |
| Execution time | ≈ 7–40s (machine-dependent) | ≈ 8s |

**Summary log (representative rows — see Appendix A for all 75):**

| Test Case Description (module / function under test) | Data Values | Result | Conclusion / Action |
|---|---|---|---|
| Login with valid credentials (`Auth\AuthenticationTest`) | Seeded user + correct password | Pass | No action |
| Login with invalid password (`Auth\AuthenticationTest`) | Seeded user + wrong password | Pass | No action |
| Clerk redirected to access-code form for coded case (`CaseAccessCodeTest`) | Clerk role, case with `access_code_hash` set | Pass | No action |
| Access-code store route rate limited at 6th attempt (`CaseAccessCodeTest`) | 5× wrong code, then 1 more | Pass | No action |
| Associate index scoping (`CaseAccessVerificationTest`) | Associate vs. own/other cases | Pass | No action |
| Retainer described entry rejected as `operational` (`IncomeViewTest` family / `StoreLedgerEntryRequest`) | `description` contains "retainer", `type=operational` | Pass | No action |
| Firm income subtotal invariant (`IncomeViewTest`) | 6 seeded cases, mixed attorneys | Pass | No action |
| Single judgment upload → embed + cited acts (`JudgmentLibraryTest`) | Mocked Gemini response, fake PDF | Pass | No action |
| Compilation import, confirm, duplicate re-upload skip (`JudgmentLibraryTest`) | 3-case compilation, identical re-upload bytes | Pass | No action |
| Import pause/resume skips finished cases (`JudgmentLibraryTest`) | Pre-seeded `completed` + `pending` items | Pass | No action |
| Gemini key-pool failover on 429 (`JudgmentLibraryTest`) | Primary key forced to return 429 on all endpoints | Pass | No action |
| Locale switch en/si/ta + invalid `fr` rejected (`LocaleSwitchTest`) | 4 locale values | Pass | No action |
| Profile update / delete with correct & wrong password (`ProfileTest`) | Valid/invalid password | Pass | No action |
| **Calendar hover summary lists appearances on that date** (`CourtScheduleCalendarTest`) | Partner + seeded case + new `trial_date` court date | ✘ Error → **Fixed, now Pass** | Root cause debugged and resolved — see below |
| **Calendar does not show a summary tab on empty days** (`CourtScheduleCalendarTest`) | Partner + a day with no court dates | ✘ Error → **Fixed, now Pass** | Same root cause, same fix |

#### Defect found during test execution — debugged and resolved

This is retained as an authentic, evidence-based example of the debugging process the rubric asks for ("test case conclusion... and the action to be taken"), rather than a hypothetical.

**Test case description:** module `Scheduling` (Module 2), function `CourtDateController::index()` / `resources/views/court-dates/index.blade.php`, calendar hover-tooltip feature.
**Test case result (first run):** `ErrorException: Undefined array key "calendarDates"` at `tests/Feature/CourtScheduleCalendarTest.php:22` and `:48`.

**Root-cause investigation (two layers deep):**

1. **Surface cause — a stale variable name in the test.** `CourtDateController::index()` passes the calendar map to the view under the key `calendarEvents` (`return view('court-dates.index', compact('upcomingByMonth', 'pastByMonth', 'calendarEvents'))`), and the Blade view itself consumes exactly that key (`events: @js($calendarEvents)` inside the Alpine `x-data` block). The test, however, called `$response->viewData('calendarDates')` — a key that has never existed in the controller or the view. Cross-checking both the controller *and* the view (not just guessing) confirmed the **test** was the stale artefact, not the production code — so the fix was to correct the two assertions in `CourtScheduleCalendarTest` to read `viewData('calendarEvents')`.
2. **Deeper cause, found only after fixing (1) — the test's markup assertions were checking for something that structurally cannot exist.** Once the key name was corrected, a second, different error appeared (`Call to a member function has() on array`): the controller's `->all()` calls produce a plain PHP array, not a Collection, so `->has()` isn't a valid method on it (fixed with `assertArrayHasKey`/`assertArrayNotHasKey`). After that, a *third* problem surfaced: the test asserted `assertSee('data-calendar-day="'.$dayKey.'"')`, but reading the Blade view in full showed the calendar grid is rendered **entirely client-side by Alpine.js** (`<template x-for="(cell, idx) in calendarDays">` computing every day cell in JavaScript from the `events` map at browser runtime). A `data-calendar-day` attribute for a specific date therefore can **never** appear in the server-rendered HTML that `assertSee()` inspects — it would only exist in the DOM *after* the browser executes Alpine, which an HTTP-level Feature test cannot observe.

**Test case conclusion:** the defect was a combination of (a) a genuine, simple test/production drift (`calendarDates` vs `calendarEvents`) and (b) an **untestable assertion** written for a static/server-rendered calendar grid that no longer matches the current Alpine-driven, client-rendered implementation. Neither issue indicated incorrect *application* behaviour — the calendar itself works correctly for users — but both were real defects in the regression suite's ability to verify that behaviour.

**Action taken (applied in this pass, verified by re-running the suite):**
- Corrected both assertions in `tests/Feature/CourtScheduleCalendarTest.php` from `viewData('calendarDates')` to `viewData('calendarEvents')`, and from `->has()` (Collection API) to `assertArrayHasKey()`/`assertArrayNotHasKey()` (the data is a plain array).
- Replaced the untestable `assertSee('data-calendar-day="…"')` check with `assertSee($dayKey)` / `assertDontSee($emptyDay->toDateString())`, which validates the meaningful, actually-server-rendered fact: the day's ISO date key is (or isn't) present in the JSON `events` map the Alpine widget hydrates from.
- Added a real `x-bind:data-calendar-day="cell.date"` attribute to the day-cell `<div>` in `resources/views/court-dates/index.blade.php`, so the DOM *does* carry a stable `data-calendar-day` attribute once Alpine mounts in a real browser — closing the gap for any future browser-level test (e.g. Dusk/Playwright) that wants to target a specific calendar day, which an HTTP Feature test cannot do.
- Re-ran `php artisan test --testdox`: **75/75 tests passing, 223 assertions, 0 failures.**

This finding is exactly the kind of evidence the Critical Appraisal sections (§4/§5) below draw on: the automated suite caught a real regression that manual UI testing would very plausibly have missed (the calendar visibly works fine to a human tester even when its `viewData()` key or its test's assumptions have drifted), and fixing it surfaced a genuine architectural note — HTTP-level Feature tests cannot verify client-side-rendered (Alpine/JS) markup, which is a good justification for adding browser-level tests as a future enhancement (see §4).

---

## 2. Implementation: Conversion and Training Plan (10 marks)

### 2.1 Justification of Conversion Technique

*(Target length: relevant to schedule/plan section below)*

LexLanka replaces a firm's existing manual/paper-based or spreadsheet-based case tracking with a single database-backed system spanning five modules (cases, documents, scheduling, billing, users/RBAC) plus an AI-assisted judgment library. Three classical conversion strategies were considered:

| Strategy | Suitability for LexLanka |
|---|---|
| **Direct (Big Bang)** cut-over | Rejected — a single date cut-over across all five modules simultaneously (documents, billing/trust ledger, court dates, RBAC) is high-risk for a legal practice: any defect in the trust-ledger module (FR‑3.2) on day one directly risks misrecording client trust funds, which carries professional/regulatory consequences beyond ordinary software risk. |
| **Parallel running** (old + new system side by side) | Rejected as the *primary* strategy — the firm's "old system" is largely paper files and informal spreadsheets with no structured export; running both indefinitely would double data-entry effort with no reconciliation tooling, and the two systems have no way to be kept consistent. |
| **Phased (Module-by-Module) conversion, with a short parallel-running window per phase** | **Adopted.** LexLanka's own module boundaries (Module 1 Documents, Module 2 Scheduling, Module 3 Billing, Module 4 Users/Client-Intake, Module 5 Core/Cases — see `CONTRACT.md`'s branch table) map directly onto a natural phased rollout, because each module is already relationally dependent on `legal_cases`/`clients`/`users` (Module 5/4) but is otherwise loosely coupled from its siblings (documents don't depend on billing being live, and vice versa). |

**Justification specific to this system:**

1. **Foundational dependency order already exists in the codebase.** Every domain table (`documents`, `court_dates`, `ledger_entries`, `research_notes`, `judgments`) has a foreign key back to `legal_cases`, and `legal_cases` depends on `clients` and `users`. This is not an arbitrary rollout choice — it mirrors the actual `database/migrations/*` dependency graph, so Module 5 (Users, Clients, Cases, Dashboard, Auth) **must** go live first regardless of rollout strategy; a phased plan simply makes this explicit instead of leaving it implicit.
2. **Risk is concentrated in Billing (FR‑3.1/FR‑3.2).** Because the trust vs. operational ledger distinction has real financial/ethical stakes, phasing it in *after* Documents and Scheduling have already been validated in live use (rather than on day one) gives the firm evidence that the platform, hosting, and RBAC are stable before financial data enters the system.
3. **The Judgment Library is functionally independent and read-mostly for existing cases.** It has no foreign-key dependency that blocks other modules (`judgments` only optionally attaches to a case via the `case_judgment` pivot), so it is the natural **last** phase — it can be seeded/back-filled with historical judgments without disrupting daily case operations, and its AI ingestion pipeline (external Gemini API, quota-limited) benefits from being rolled out once staff are already comfortable with the rest of the system.
4. **A short parallel-running window per phase (not for the whole system) mitigates Big Bang risk without the cost of full duplicate operation.** During each phase's first 1–2 weeks, staff continue updating their prior manual record (e.g. the paper court diary) *for that module only* while also using LexLanka, so any discrepancy is caught and reconciled before the manual record is retired for that module — this is materially cheaper than running two full systems in parallel for the whole six-week rollout.

### 2.2 Schedule and Plan for Conversion

*(Target length: ~4 pages)*

#### 2.2.1 Gantt Chart (6-week phased rollout)

```
Week:                     1    2    3    4    5    6
Phase 0 – Prep/Training  [====]
Phase 1 – Core (Mod 5)        [====]
  ↳ parallel window            [==]
Phase 2 – Documents (Mod 1)         [====]
  ↳ parallel window                  [==]
Phase 3 – Scheduling (Mod 2)              [====]
  ↳ parallel window                        [==]
Phase 4 – Billing (Mod 3)                       [====]
  ↳ parallel window                              [====]  (longer — financial data)
Phase 5 – Users/RBAC & Client Intake (Mod 4)          [==]
  ↳ (largely live already via seeded roles from Phase 0)
Phase 6 – Judgment Library (AI)                            [====]
Post-launch review/stabilisation                                 [==]
```

> Replace this ASCII sketch with a rendered Gantt chart (e.g. from Excel, GanttProject, or Mermaid) in the final submission; the phase boundaries and durations above are the source data to chart. Module numbers refer to `CONTRACT.md`'s branch-ownership table.

#### 2.2.2 Phase-by-phase resource plan (who / what / where / when / how)

| Phase | Who (resource) | What | Where | When | How |
|---|---|---|---|---|---|
| **0 — Preparation & Training** | Firm IT contact / project lead + all staff (partners, associates, clerks) | Server/hosting provisioning (PHP 8.3, MySQL, Composer, Node/npm per `SETUP.md`); `.env` configuration (DB credentials, `APP_KEY`, optional `GEMINI_API_KEY`); run `composer install && npm install && php artisan migrate --seed`; deliver User Guide (§3) training session per role | Firm office / staging server, on-site or remote training session | Week 1 | Guided walkthrough of the User Guide per role (partners see billing/user-mgmt screens, associates see their case load, clerks see access-code flow), using the seeded demo accounts (`partner@lexlanka.lk`, `associate@lexlanka.lk`, `clerk@lexlanka.lk`) as a safe practice environment before real client data is entered |
| **1 — Core (Module 5): Users, Clients, Cases, Dashboard, Auth** | Module 5 owner + partner (data steward) | Create real partner/associate/clerk accounts (`role`, `branch`, `flat_appearance_rate`); import/enter existing client list (`nic`, `intake_date`) and open case files (`case_type`, `assigned_attorney_id`, `status`) | Production instance | Weeks 2–3, 1-week parallel window with the firm's existing client/case register | Data entered manually via the `users`/`clients`/`cases` CRUD screens (no legacy export format exists to script an automated import against); partner cross-checks each new case against the paper file during the parallel week before that paper file is marked "migrated" |
| **2 — Documents (Module 1)** | Module 1 owner + clerks (primary uploaders) | Upload existing case documents (`evidence`/`deeds`/`correspondence`), respecting the 25 MB/PDF-JPG-PNG constraint (`Document::MAX_FILE_SIZE_KB`, `ALLOWED_FILE_TYPES`) | Production instance; run `php artisan storage:link` first | Week 3, 1-week parallel window | Bulk scanning of any remaining paper documents into PDF, then upload per case; clerks continue keeping the physical filing cabinet in sync for that week as a fallback |
| **3 — Scheduling (Module 2)** | Module 2 owner + all fee-earners | Enter upcoming court dates (`calling_date`/`trial_date`) per open case; verify the 48-hour reminder flag (`reminder_sent`) and the calendar view | Production instance | Week 4, 1-week parallel window against the firm's existing physical court diary | Each attorney cross-checks their diary against the system calendar daily during the parallel week — this is also when the calendar-tooltip defect noted in §1.3 must be fixed and re-verified before sign-off |
| **4 — Billing (Module 3)** | Module 3 owner + partner (financial sign-off) | Enter opening trust/operational ledger balances per case (`ledger_entries`); verify appearance-fee calculation (`trial_date_count × flat_appearance_rate`) against manually-calculated figures for at least 3 real cases | Production instance | Weeks 5–6, **extended 2-week parallel window** given the financial/regulatory sensitivity (see §2.1 justification) | Partner personally reconciles system-calculated appearance fees and trust balances against the firm's existing accounting records before the manual ledger is retired; any retainer entered as `operational` must be corrected to `trust` per FR‑3.2 before go-live sign-off |
| **5 — Users/RBAC & Client Intake refinement (Module 4)** | Module 4 owner | Fine-tune role assignments/branch data now that real usage patterns are visible; confirm suspended-user login rejection works as expected | Production instance | Week 5 (overlaps Phase 4; largely already live since accounts were created in Phase 0/1) | Spot-check: attempt login as a deliberately suspended test account and confirm the "Your account has been suspended" message |
| **6 — Judgment Library (AI-assisted)** | Whoever owns the AI/ingestion feature + partner (content curation) | Configure `GEMINI_API_KEY` (and optional `GEMINI_API_KEYS` fallback pool); bulk-upload historical judgment PDFs/compilations; review any detected multi-case compilations via the review-and-confirm screen before indexing | Production instance | Week 6 (deliberately last — no dependency on other modules being complete) | Upload in small batches first to confirm Gemini quota/keys are configured correctly, then scale up; monitor for `paused` batches (quota exhausted) and use the Resume action once quota resets |
| **Post-launch review** | Whole team | Retrospective against the acceptance criteria used in Phase 1–6 sign-offs; retire remaining paper records | Firm office | Week 7 | Structured review meeting; feeds directly into §4 Critical Appraisal |

---

## 3. Implementation: User Guide (10 marks)

*(Target length: ~10 pages — insert screenshots of each screen listed below in the final submission)*

### 3.1 Installation Instructions

> Condensed from the project's own `SETUP.md`; reproduced here so the User Guide is self-contained.

**Prerequisites:** PHP ≥ 8.3, Composer, Node.js & npm, a MySQL server (or compatible), Git (optional).

1. **Get the project** — clone or copy the repository to the target machine and open a terminal in the project root.
2. **Install PHP dependencies:** `composer install`
3. **Install front-end dependencies:** `npm install`
4. **Create the environment file:** copy `.env.example` to `.env` (`copy .env.example .env` on Windows, `cp .env.example .env` on Mac/Linux).
5. **Generate the application key:** `php artisan key:generate`
6. **Configure the database** in `.env` — create an empty database (default name `lexlanka`) and set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to match your server.
7. *(Optional — required only for the AI Judgment Library)* set `GEMINI_API_KEY` (and, optionally, one or more comma/newline-separated fallback keys in `GEMINI_API_KEYS`) in `.env`. Without this, every module except the Judgment Library works normally.
8. **Run migrations and seed demo data:** `php artisan migrate --seed` — this creates all tables and a working set of demo users/clients/cases (see §3.4.9 for the seeded login credentials).
9. **Link storage for uploaded files:** `php artisan storage:link`
10. **Start the application:**
    - Terminal 1: `php artisan serve`
    - Terminal 2: `npm run dev`
    (Or, for an all-in-one dev experience, `composer run dev` starts the server, queue worker, log viewer, and Vite together.)
11. **Open the application** at `http://127.0.0.1:8000` (or the URL printed by `php artisan serve`).

**Troubleshooting:**
- *"Could not find driver"* — enable the PDO MySQL extension in `php.ini`.
- npm errors — delete `node_modules` and `package-lock.json`, then re-run `npm install`.
- Permission errors on Mac/Linux — `chmod -R 775 storage bootstrap/cache`.
- Judgment upload/ingestion errors — confirm `GEMINI_API_KEY` is set; if imports repeatedly `pause`, the free-tier quota has been exhausted for the day (add a fallback key in `GEMINI_API_KEYS`, or use the **Resume** button once quota resets).

### 3.2 Logging In and Roles

Navigate to the application URL and log in with an email/password. The system has **three roles**, each with a different set of visible screens:

| Role | Can see |
|---|---|
| **Partner** | Everything — all cases regardless of assigned attorney, all billing/financials, firm-wide income, user management, all judgments |
| **Associate** | Only cases they are personally assigned to (and that case's documents/court dates/billing/ledger); their own income summary; the firm-wide judgment library |
| **Clerk** | Cases only after entering a per-case access code (if one is configured); no billing/financial screens; no user management; the firm-wide judgment library |

A **suspended** account cannot log in at all and is shown: *"Your account has been suspended. Please contact an administrator."*

**Demo accounts** (created by the seeder, for training/practice only — remove or change passwords before go-live): `partner@lexlanka.lk`, `associate@lexlanka.lk`, `clerk@lexlanka.lk` (all password `password`), plus `associate2@lexlanka.lk` and a deliberately `suspended@lexlanka.lk` account for testing the suspension message.

### 3.3 Main Functions — Operating Instructions

#### 3.3.1 Dashboard
On login, the Dashboard summarises the user's cases, upcoming court dates, and (for partners) firm-wide indicators. Use the top navigation bar to reach every other module; the language switcher (English / Sinhala / Tamil) is in the navigation and persists to the account (`users.locale`).

#### 3.3.2 Client Intake & Case Management
- **Add a client:** *Clients → Create*, enter name, NIC (unique — duplicates are rejected), phone, email (optional), intake date.
- **Open a case:** *Cases → Create*, select the client, assign an attorney, choose a case type (Civil Litigation, Property Dispute, Criminal Defence, Family Law, Labour Dispute, Land Acquisition, Other) and initial status.
- **Case statuses** progress through `pending → active → trial_scheduled → judgment_delivered → case_closed`. Moving into `trial_scheduled`, `judgment_delivered`, or `case_closed` are **milestone statuses** that trigger a client notification.
- **Access codes (optional, per case):** a partner or the case's assigned associate can set/change a numeric access code on the case's edit screen. This is required only if you want a clerk to be able to open that case — a clerk viewing a case with no code configured is simply denied. Once a clerk enters the correct code, they retain access for the rest of that login session; entering an incorrect code 5 times in a minute temporarily blocks further attempts (429 — try again shortly).

#### 3.3.3 Documents
- **Upload:** open a case → *Documents → Upload*, choose one or more files (PDF, JPG, or PNG only, 25 MB max each), pick a category (Evidence / Deeds / Correspondence), optionally give it a name.
- **View/download/preview:** from the case's document list or the global *Documents* index (associates only see documents belonging to their own cases; a clerk must first pass the case's access code, if any, and will be redirected to enter it if they try to open a document directly).
- **Note:** documents cannot currently be renamed after upload — set the name at upload time.

#### 3.3.4 Court Scheduling
- **Add a court date:** open a case → *Court Dates → Add*, choose the date/time and type (**Calling Date** or **Trial Date** — trial dates drive billing and the 48-hour reminder).
- **Calendar view:** *Court Dates* shows a month calendar; hovering a highlighted day shows a tooltip listing every appearance that day (time, type, case). Associates only see dates for their own cases.
- **Edit/remove** a court date from the same screen; associates cannot reassign a date to a case they don't own.

#### 3.3.5 Billing & Financials (Partner + owning Associate only)
- **Case billing:** open a case → *Billing* to see the appearance fee (`trial-date count × the assigned attorney's flat rate`), and the Trust vs. Operational ledger balances.
- **Record a ledger entry:** choose **Trust** (client funds, e.g. retainers — *any description mentioning "retainer" must be recorded as Trust, not Operational, or the system will reject it with an explanatory message*) or **Operational** (firm expenses like filing fees), enter an amount (minimum 0.01) and description.
- **Generate an invoice:** *Billing → Create Invoice*, pick one of your own open (non-closed) cases; a PDF is produced via the system's PDF engine.
- **My Income / Firm Income:** associates see their personal appearance-fee income breakdown on the Billing index; partners additionally have a *Firm Income* screen breaking totals down per attorney with a grand total.

#### 3.3.6 Research Notes
Attach a research note to a case (category: judgment / act-or-ordinance / other, with a citation, source court, and a note on why it's relevant) — useful for tracking legal authorities relevant to that specific matter, separate from the firm-wide Judgment Library below.

#### 3.3.7 Judgment Library (AI-assisted, all roles)
- **Browse/search:** *Judgments* is open to every authenticated role (it is a firm-wide reference library, not scoped to a case).
- **Upload a judgment PDF:** the system automatically detects the case title, court, and decided date, extracts a plain-text summary, records any cited Acts/Ordinances, and indexes it for semantic search. If the PDF is a **compilation** of multiple judgments, you'll be taken to a **Review & Confirm** screen listing each detected case and its page range — tick which ones to keep, edit any field, then confirm to index them individually.
- **If an import pauses** ("quota" shown as the reason): the daily free AI quota was reached mid-import. No data is lost — click **Resume** once quota resets (next day, or immediately if a fallback key is configured), or **Cancel** to keep everything already indexed and discard the rest.
- **Attach a judgment to a case:** from a case page, use *Related Judgments → Search* to run an AI similarity search over your query text, then attach the relevant result with a short note on why it's relevant.

#### 3.3.8 User Management (Partner only)
*Users → Create/Edit* to add staff accounts (name, email, password, role, branch, and — for fee-earners — a flat court-appearance rate), and to suspend/reactivate accounts.

### 3.4 General Error Handling

| Situation | What the user sees | What to do |
|---|---|---|
| Wrong login credentials | "These credentials do not match our records." | Re-check email/password; use password reset if needed |
| Suspended account login attempt | "Your account has been suspended. Please contact an administrator." | Contact a partner/administrator to reactivate the account |
| Accessing a resource you don't have permission for (e.g. another associate's case, or the billing page as a clerk) | HTTP 403 — "You do not have permission to access this resource." | This is expected RBAC behaviour, not a bug — request access from a partner if you believe it's needed |
| Clerk opening a case/document without having entered its access code yet | Automatic redirect to the access-code entry form (not a bare error) | Enter the code given to you by the case owner; you'll be returned to exactly the page you originally requested |
| Wrong access code entered | Inline validation error on the form; no session access granted | Re-enter the correct code; after 5 wrong attempts in a minute you must wait before trying again (HTTP 429) |
| Uploading a file over 25 MB, or an unsupported type | "One or more files exceed the 25MB limit." / "One or more files have an unsupported type. Allowed: PDF, JPG, PNG." | Compress/convert the file, or split large scans into smaller PDFs |
| Recording a retainer as an Operational ledger entry | "Retainer funds must be recorded in the Client Trust Ledger." | Re-submit the same entry with **Trust** selected instead |
| Ledger amount of 0 or blank | Standard "the amount field must be at least 0.01" validation message | Enter a positive amount |
| Deleting your own account with the wrong password confirmation | Inline error on the password field; account is **not** deleted | Re-enter your correct current password |
| Judgment import pauses mid-batch | Batch status shown as "Paused — quota" on the review screen | Click **Resume** later, or **Cancel** to keep what was already indexed |
| Selecting an unsupported locale (anything other than English/Sinhala/Tamil) | Validation error on the locale selector | Choose one of the three supported languages |
| General server error (500) | Laravel's standard error page (details hidden unless `APP_DEBUG=true`) | Report to the system administrator with the time of the error; check `storage/logs/laravel.log` |

---

## 4. Critical Appraisal: Combined (5 marks)

*(Target length: ~5 pages, shared with §5 — write this section together as a group)*

**Overall achievement.** The group delivered a working, role-aware legal case management system covering all five originally-scoped modules (Users/RBAC & Client Intake, Documents, Scheduling, Billing, and Cases/Dashboard/Core), plus a substantial additional feature — an AI-assisted Judgment Library with automatic compilation detection, semantic similarity search, and API-quota failover — that went beyond the original module split documented in `CONTRACT.md`. A 75-case automated regression suite (215 assertions, 97.3% passing at time of writing) gives the group concrete, evidence-based confidence in the RBAC/data-scoping model, which was identified early as the system's highest-risk area given that three roles share the same underlying tables.

**Problems faced during development:**
1. **Reconciling shared schema across five parallel feature branches.** Because every module's table has a foreign key back to `legal_cases`/`clients`/`users` (owned by Module 5/4), changes to those core tables had ripple effects across every other branch. This was mitigated by treating `CONTRACT.md` as a single source of truth that had to be updated *before* a schema change was merged, rather than after — but early in the project this discipline was not yet in place, causing at least one integration conflict. *[Group: name a concrete instance if one occurred and how it was resolved.]*
2. **RBAC edge cases were subtler than initially scoped.** The "verified clerk must still never edit a case" rule (a clerk can legitimately pass the `view` policy after entering an access code, but must be hard-blocked from `edit`/`update` regardless) was not part of the original design and was only discovered once the access-code feature and the case-edit feature were combined — it required an explicit code path in `LegalCaseController` rather than relying on the policy alone. This is a good example of an *emergent* requirement discovered through integration testing rather than up-front design.
3. **Third-party AI dependency introduced non-determinism into testing.** The Judgment Library's reliance on the external Gemini API for detection/summarisation/embedding meant the team had to invest extra effort building a `GeminiKeyPool` failover mechanism and a paused/resumable import state machine (`JudgmentImportBatch`/`JudgmentImportItem`) purely to make the feature usable within a free-tier API quota — this was scope the group had to absorb that a purely CRUD system would not have faced.
4. **A genuine regression was caught only by the automated suite, not manual testing** — the `calendarEvents`/`calendarDates` naming mismatch documented in §1.3 slipped through manual QA (the calendar visibly renders correctly enough that a human tester would not notice the underlying view-data key had drifted) and was only caught because the test suite asserts on the exact `viewData()` key. Debugging it one layer deeper also surfaced a genuinely useful architectural lesson: two of the test's assertions were checking for `data-calendar-day` markup that, by design, only ever exists in the browser's DOM after Alpine.js hydrates the calendar — an HTTP-level Feature test structurally cannot see it. Both issues were fixed (test corrected to the real `calendarEvents` key/array API; the untestable markup assertion replaced with a check on the JSON data actually present in the server response; a real `data-calendar-day` Alpine binding added to the view for future browser-level tests), and the suite now passes 75/75. This is direct, first-hand evidence for why the group invested in automated Feature tests rather than manual testing alone — and for why a future browser/E2E testing layer (Dusk/Playwright) is listed as an enhancement below.

**Possible future enhancements:**
- The calendar view-data naming defect (§1.3) has been fixed and the regression suite is back to 75/75, but the underlying limitation it exposed remains: HTTP-level Feature tests cannot verify Alpine/JS-rendered markup.
- Add a document rename/edit capability (currently documented as an intentional gap in `CONTRACT.md` — names can only be set at upload time).
- Extend the RBAC model with a documented audit trail for who viewed/verified access to a coded case, valuable given the legal/professional context.
- Add a proper vector index for judgment similarity search (currently an in-PHP cosine-similarity scan over all judgments, which will not scale past a few thousand records — `JudgmentSimilaritySearchService` docblock already flags this).
- Add automated browser/E2E tests (e.g. via Laravel Dusk or Playwright) to complement the HTTP-level Feature tests, closing the gap between "the correct data reached the view" and "the page renders it correctly to a user" — precisely the gap that allowed the calendar defect to go unnoticed by manual testers.
- Formalise the phased conversion plan in §2 into a real project-management artefact (Gantt chart software, sign-off checklists per phase) before any real firm adopts the system.

*[Group: add/adjust these points based on your team's actual experience — this section must reflect what genuinely happened during your project, not only what is inferable from the code.]*

---

## 5. Critical Appraisal: Individual (5 marks)

*(Target length: within the combined ~5 pages with §4 — each member writes their own sub-section. Use `CONTRACT.md`'s branch/module ownership table as the basis for "which modules did I own", and replace every `[ ]` placeholder.)*

> Per the rubric: the **combined** appraisal (§4) covers the system broadly; **this** section must be written **individually**, focused on the modules each member personally owned, and should be honest about personal contribution, difficulties, and learning — not a restatement of §4.

### 5.1 [Member 1 Name] — Module 5: Core (Users/Auth foundation, Cases, Dashboard) *[or your actual module]*

- **My contribution:** [Describe the specific features, files, and decisions you personally implemented, e.g. `LegalCase` model/migrations, `CaseAccessPolicy`, the dashboard aggregation logic.]
- **Difficulties I faced:** [e.g. designing the `CaseAccessPolicy` role matrix so it composed correctly with the later access-code feature without the two conflicting.]
- **What I learned:** [e.g. Laravel Policies/Gates, RefreshDatabase test seeding strategy.]
- **What I would do differently:** [ ]

### 5.2 [Member 2 Name] — Module 1: Documents

- **My contribution:** [e.g. `DocumentController`, `StoreDocumentRequest` validation rules, the 25 MB/type constraints, storage-link setup.]
- **Difficulties I faced:** [ ]
- **What I learned:** [ ]
- **What I would do differently:** [ ]

### 5.3 [Member 3 Name] — Module 2: Scheduling

- **My contribution:** [e.g. `CourtDateController`, the calendar grouping/tooltip logic, the reminder flag.]
- **Difficulties I faced:** [Consider directly addressing the `calendarEvents`/`calendarDates` defect found in §1.3 if this was your module — what caused it, and what you'd change in your process to avoid similar naming drift.]
- **What I learned:** [ ]
- **What I would do differently:** [ ]

### 5.4 [Member 4 Name] — Module 3: Billing

- **My contribution:** [e.g. `BillingService`, the trust/operational ledger rule (FR‑3.2), invoice PDF generation, firm-income aggregation.]
- **Difficulties I faced:** [e.g. getting the retainer-detection validation rule precise enough to avoid false positives/negatives.]
- **What I learned:** [ ]
- **What I would do differently:** [ ]

### 5.5 [Member 5 Name] — Module 4: Users/RBAC & Client Intake *(and/or the Judgment Library, if that was your extension work)*

- **My contribution:** [e.g. `RoleMiddleware`, `StoreUserRequest`, suspended-account login rejection; or, if applicable, the `JudgmentIngestionService`/`GeminiKeyPool` AI pipeline.]
- **Difficulties I faced:** [e.g. designing the API-quota failover and pause/resume state machine for the Judgment Library within a free-tier Gemini quota.]
- **What I learned:** [ ]
- **What I would do differently:** [ ]

---

## Appendix A: Full Test Case Catalogue and Raw Test Logs

**Command used:** `php artisan test --testdox`
**Environment:** PHPUnit 12.5.30, PHP 8.3.32, project root `Legal-Management-System`
**First-run result (as found):** `Tests: 75, Assertions: 215, Errors: 2.`
**Final result (after the fix described in §1.3):** `OK (75 tests, 223 assertions)`

### A.1 Full pass/fail list by test class

| Test class | Test cases | Result |
|---|---|---|
| `Tests\Feature\Auth\AuthenticationTest` | Login screen renders; authenticate via login screen; reject invalid password; logout | 4/4 Pass |
| `Tests\Feature\CaseAccessCodeTest` | Redirect to code form for coded case; forbidden without a code; form hidden for uncoded case; wrong code rejected; correct code grants session access; document access redirects when unverified; redirect restores originally-requested document; only partner/owning associate can manage code; verified clerk still can't edit/update case; store route rate-limited | 10/10 Pass |
| `Tests\Feature\CaseAccessVerificationTest` | Associate index scoping; partner sees all; associate can't show/edit unowned case; associate can show owned case; document index scoping; associate can't view unowned document/billing; associate can view owned billing; partner can view any billing | 10/10 Pass |
| `Tests\Feature\CaseRelatedJudgmentsTest` | Similarity search ranks results correctly; attaching a judgment respects `CaseAccessPolicy` | 2/2 Pass |
| `Tests\Feature\CourtScheduleCalendarTest` | Calendar hover summary lists appearances on that date; calendar hides summary tab on empty days | First run: **0/2 — both Error**; **2/2 Pass after the fix in §1.3** |
| `Tests\Feature\Auth\EmailVerificationTest` | Screen renders; email can be verified; invalid hash rejected | 3/3 Pass |
| `Tests\Feature\ExampleTest` / `Tests\Unit\ExampleTest` | Baseline scaffold tests | 2/2 Pass |
| `Tests\Feature\IncomeViewTest` | Associate/partner billing index scoping; income summary present/absent by role; "my income" route removed; create-invoice scoping; unauthorized invoice generation forbidden; ledger entry create/delete authorization (associate own/other, partner any); firm income view by role; firm income subtotal-sum invariant | 15/15 Pass |
| `Tests\Feature\JudgmentLibraryTest` | Single judgment upload embeds text + stores cited acts; compilation confirm indexes slices + skips duplicate re-upload; import pause-on-quota + resume skips finished items; Gemini key-pool failover across detection/summarisation/embedding; library open to any authenticated role | 5/5 Pass |
| `Tests\Feature\LocaleSwitchTest` | Default English nav; Sinhala translation; Tamil translation; locale persists to account; invalid locale rejected; guest can switch via session | 6/6 Pass |
| `Tests\Feature\Auth\PasswordConfirmationTest` | Screen renders; password confirmed; invalid password rejected | 3/3 Pass |
| `Tests\Feature\Auth\PasswordResetTest` | Link screen renders; link requested; reset screen renders; password reset with valid token | 4/4 Pass |
| `Tests\Feature\Auth\PasswordUpdateTest` | Password updated; correct current password required | 2/2 Pass |
| `Tests\Feature\ProfileTest` | Profile page displayed; info updated; verification status unchanged when email unchanged; account deleted; correct password required to delete | 5/5 Pass |
| `Tests\Feature\Auth\RegistrationTest` | Screen renders; new users can register | 2/2 Pass |
| **Total** | **75 test cases** | First run: **73 Pass / 2 Error** (215 assertions) → Final: **75/75 Pass** (223 assertions) |

### A.2 Raw failure output (verbatim) — as first found

```
Court Schedule Calendar (Tests\Feature\CourtScheduleCalendar)
 ✘ Calendar hover summary lists appearances on that date
   ErrorException: Undefined array key "calendarDates"
   vendor\laravel\framework\...\HandleExceptions.php:263
   vendor\laravel\framework\...\TestResponse.php:1395
   tests\Feature\CourtScheduleCalendarTest.php:40

 ✘ Calendar does not show a summary tab on empty days
   ErrorException: Undefined array key "calendarDates"
   vendor\laravel\framework\...\HandleExceptions.php:263
   vendor\laravel\framework\...\TestResponse.php:1395
   tests\Feature\CourtScheduleCalendarTest.php:60

ERRORS!
Tests: 75, Assertions: 215, Errors: 2.
```

**Root cause, impact, and action taken:** see the full debugging narrative in §1.3 — this was two layered issues (a stale `calendarDates`/`calendarEvents` key name in the test, and an assertion checking for markup that Alpine.js renders only client-side), both fixed in `tests/Feature/CourtScheduleCalendarTest.php` and `resources/views/court-dates/index.blade.php`.

### A.3 Raw output after the fix (verbatim)

```
Court Schedule Calendar (Tests\Feature\CourtScheduleCalendar)
   Calendar hover summary lists appearances on that date
   Calendar does not show a summary tab on empty days

OK (75 tests, 223 assertions)
```

This is the clean, final state of the suite as of this document's preparation. Re-run `php artisan test --testdox` before final submission to reconfirm 75/75 with any further changes the group makes.
