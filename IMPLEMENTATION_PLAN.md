# LexLanka — Feature Addition Implementation Plan

> **Audience:** any AI coding agent (or developer) picking up this work with no prior context beyond this repo.
> **Read first:** `CONTRACT.md` (schema + RBAC + naming conventions — non-negotiable) and `DOCUMENTATION_DEVELOPMENT_IMPLEMENTATION.md` (testing conventions).
> **Do not invent columns, Gates, or route names that conflict with `CONTRACT.md`.** If a task below adds a table/Gate, update `CONTRACT.md` in the same PR.

---

## 0. How to work through this plan

Each task below is independent enough to be its own branch/PR, but is numbered in the recommended build order (dependencies are called out explicitly). For **every** task, follow this sequence:

1. **Migration** (if any) — filename pattern `YYYY_MM_DD_HHMMSS_verb_noun.php`, matching the existing `database/migrations/` style (see `2026_09_14_100003_add_taxonomy_fields_to_legal_cases_table.php` for an "add columns" example, `2026_08_24_100001_create_research_notes_table.php` for a "create table" example).
2. **Model** — `snake_case` columns, `UPPER_SNAKE_CASE` constants for enums (see `LegalCase::APPLICABLE_LAWS`, `ResearchNote::CATEGORIES`).
3. **Form Request** — name pattern `Store{Model}Request` / `Update{Model}Request`, `authorize()` reuses `Gate::allows('view', $case)` wherever the resource hangs off a case (this is the established pattern — see `StoreResearchNoteRequest`, `SearchCaseJudgmentsRequest`).
4. **Controller** — thin; business logic goes in a `App\Services\*Service` class when there's real logic (see `BillingService`, `JudgmentSimilaritySearchService`).
5. **Routes** — add to the relevant existing `routes/*.php` file (`cases.php`, `documents.php`, `billing.php`, `research.php`, `judgments.php`, `scheduling.php`, `taxonomy.php`, `users.php`) or a new file registered in `routes/web.php`. Follow existing name conventions: `resource.action` (e.g. `cases.related-judgments.store`).
6. **Authorization** — reuse `CaseAccessPolicy::view` for anything scoped to a case. Reuse the `view-financials` / `manage-users` / `manage-taxonomy` Gates where they fit. Do not create a new Gate unless nothing existing fits — if you do, add it to `AppServiceProvider::boot()` **and** the Gates table in `CONTRACT.md`.
7. **Views** — Blade + Tailwind + Alpine.js, matching the tabbed pattern already on `resources/views/cases/show.blade.php` (`x-data="{ activeTab: ... }"`).
8. **Tests** — Feature tests under `tests/Feature/`, following the authorization-matrix style already used (`tests/Feature/CaseRelatedJudgmentsTest.php`, `tests/Feature/JudgmentLibraryTest.php`): same operation as each of the 3 roles, owned vs non-owned case. Use `Http::fake()` for any Gemini calls — never hit the real API in tests.
9. **Update `CONTRACT.md`** if you added a table, column, Gate, or route file.
10. Run `composer test` (clears config cache, runs `php artisan test`) before opening the PR. All existing tests must still pass — several tasks touch shared files (`LegalCaseController`, `cases/show.blade.php`, `routes/billing.php`) so regressions are easy to introduce.

**Branch naming:** `feature/<short-task-slug>` (e.g. `feature/appearance-fee-fix`, `feature/stamp-duty-calculator`), consistent with the existing `feature/{module}` convention in `CONTRACT.md`.

---

## Build order

| Phase | Tasks | Why this order |
|---|---|---|
| 1 — Fix what's already claimed | 1, 2, 3, 4 | Bugs and half-built features in shipped code; zero new dependencies |
| 2 — Standalone calculators | 10, 11 | No AI, no new relationships, high value, fully isolated |
| 3 — Case productivity | 8, 16, 14, 15 | Additive tables/routes, no touching Gemini pipeline |
| 4 — Research AI | 5, 6, 7 | Depends on Phase 3 patterns; extends existing Gemini services |
| 5 — Drafting | 12, 13 | Extends `DocumentController::generatePdf`; do after research AI so the same Gemini chat helper (Task 5) can be reused for draft suggestions |
| 6 — Citation graph | 9 | Most speculative; depends on ingestion schema changes, do last |

---

## Phase 1 — Fix what's already claimed

### Task 1 — Appearance fee bug on case show page

**Problem:** `BillingService::calculateAppearanceFee()` and `LegalCase::totalAppearanceFee()` correctly count only `trial_date` court dates. `LegalCaseController::show()` does not use either — it multiplies **all** court dates (trial + calling) by the attorney's rate.

**File:** `app/Http/Controllers/LegalCaseController.php`, inside `show()` (~line 138-146):

```php
// Before
$courtDateCount = $case->courtDates->count();
$attorney = $case->assignedAttorney;
$totalAppearanceFee = $attorney ? $courtDateCount * ($attorney->flat_appearance_rate ?? 0) : 0;

// After
$totalAppearanceFee = $case->assignedAttorney ? $case->totalAppearanceFee() : 0;
```

No migration, no route change, no view change (the view already just prints `$totalAppearanceFee`).

**Test:** Add/extend a feature test asserting that a case with e.g. 2 `trial_date` + 3 `calling_date` entries shows a fee based on **2**, not 5, trial dates. Check `tests/Feature/IncomeViewTest.php` first — this may be the right file to extend rather than creating a new one.

---

### Task 2 — Document rename

**Problem:** `routes/documents.php` excludes `edit`/`update` from the resource route. `documents.name` column already exists (`Document` model, migration `2026_08_20_100004_add_name_to_documents_table.php`) but is only settable at upload time.

**No migration needed.**

1. **Request:** `app/Http/Requests/RenameDocumentRequest.php`
   - `authorize()`: `$document = $this->route('document'); return $document->uploaded_by === $this->user()->id || Gate::allows('manage-users');` (mirrors the research-note delete rule in `ResearchNoteController::destroy`).
   - `rules()`: `['name' => ['required', 'string', 'max:255']]`.
2. **Controller:** add `rename(RenameDocumentRequest $request, Document $document)` to `DocumentController` — update `name`, redirect back with `success` flash.
3. **Route:** in `routes/documents.php`, add inside the existing `auth` group (keep `->except(['edit','update'])` on the resource — don't touch it):
   ```php
   Route::patch('/documents/{document}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
   ```
4. **View:** small inline rename form (name input + save button) on `resources/views/documents/show.blade.php`, gated by the same ownership check used in the request (`@if($document->uploaded_by === auth()->id() || Gate::allows('manage-users'))`).
5. **Test:** `tests/Feature/DocumentRenameTest.php` — uploader can rename; a different associate on a different case cannot (403); partner can always rename.

---

### Task 3 — Fix and surface global search

**Problems:**
- `routes/documents.php` defines `GET /search` → `SearchController::index`, but `resources/views/layouts/navigation.blade.php`'s `$navLinks` array has no entry for it — the page is unreachable from the UI.
- `SearchController::index()` matches documents on `file_path` only, not the human-readable `name` column.

1. **`app/Http/Controllers/SearchController.php`** — change the documents query:
   ```php
   $documents = Document::with(['legalCase.client', 'uploader'])
       ->where(function ($q) use ($term) {
           $q->where('name', 'LIKE', $term)
             ->orWhere('file_path', 'LIKE', $term);
       })
       ->limit(50)
       ->get();
   ```
2. **`resources/views/layouts/navigation.blade.php`** — add to the `$navLinks` array (after `clients.index`, before the conditional financial/user links):
   ```php
   ['route' => 'search.index', 'label' => __('Search')],
   ```
3. **Test:** add `tests/Feature/SearchTest.php` — seed a document with `name = 'Deed of Sale'` and `file_path = 'documents/random-uuid.pdf'`, search for `Deed`, assert it's returned.

---

### Task 4 — Real notification channels (mail for milestones, pluggable SMS)

**Problem:**
- `App\Notifications\Channels\SmsChannel` only does `Log::info(...)` — no SMS is ever actually sent, to anyone, ever.
- `App\Notifications\CaseMilestoneNotification` (client-facing, on `trial_scheduled` / `judgment_delivered` / `case_closed`) only declares the `SmsChannel` — clients with an email on file never get anything real.
- `App\Notifications\TrialReminderNotification` (attorney-facing) already correctly uses `mail` + `SmsChannel` — leave it as reference.

**Part A — add mail to the milestone notification:**

1. Open `app/Notifications/CaseMilestoneNotification.php`.
2. Change `via($notifiable)` to:
   ```php
   public function via($notifiable): array
   {
       $channels = [SmsChannel::class];

       if (! empty($notifiable->email)) {
           $channels[] = 'mail';
       }

       return $channels;
   }
   ```
3. Add `toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage` reusing the same milestone copy already built for `toSms()` (refactor the message text into a shared private method if it's currently only inline in `toSms()`).
4. `Client` model already has an `email` column — Laravel's default `routeNotificationForMail()` uses the `email` attribute automatically, no extra method needed (mirrors how `phone` already works via `routeNotificationForSms()`).

**Part B — make SMS pluggable instead of a permanent stub:**

Do **not** hardcode a specific SMS vendor (none has been chosen for this firm yet). Instead, add a swappable interface so a real gateway can be dropped in later without touching notification classes:

1. **`app/Services/Sms/SmsGatewayInterface.php`**:
   ```php
   interface SmsGatewayInterface
   {
       public function send(string $phone, string $message): bool;
   }
   ```
2. **`app/Services/Sms/LogSmsGateway.php`** — the current behavior, extracted verbatim (`Log::info(...)`, returns `true`). This stays the **default**.
3. **`app/Services/Sms/HttpSmsGateway.php`** — a generic HTTP POST gateway reading `config('services.sms.endpoint')`, `config('services.sms.api_key')`, `config('services.sms.sender_id')`. Leave the payload shape as a clearly marked `// TODO: adjust to the chosen provider's API` — this is a plug point, not a finished integration, because no provider is selected yet.
4. **`config/services.php`** — add:
   ```php
   'sms' => [
       'driver' => env('SMS_DRIVER', 'log'), // 'log' or 'http'
       'endpoint' => env('SMS_ENDPOINT'),
       'api_key' => env('SMS_API_KEY'),
       'sender_id' => env('SMS_SENDER_ID'),
   ],
   ```
5. **`app/Providers/AppServiceProvider.php`** — bind the interface in `register()`:
   ```php
   $this->app->bind(SmsGatewayInterface::class, fn () =>
       config('services.sms.driver') === 'http'
           ? new HttpSmsGateway()
           : new LogSmsGateway()
   );
   ```
6. **`app/Notifications/Channels/SmsChannel.php`** — resolve `SmsGatewayInterface` from the container (constructor injection) and call `->send($phone, $message)` instead of `Log::info()` directly.
7. **`.env.example`** — add `SMS_DRIVER=log`, `SMS_ENDPOINT=`, `SMS_API_KEY=`, `SMS_SENDER_ID=` with a comment that `log` is safe for dev/demo.

**Test:**
- `tests/Unit/SmsChannelTest.php` — bind a fake `SmsGatewayInterface` in the container, assert it's called with the right phone/message.
- Extend whatever test currently covers `CaseMilestoneNotification` (check for one under `tests/Feature/` covering case status transitions) to assert `Notification::fake()` sees a `mail` channel dispatched when the client has an email, and does not when it doesn't.

---

## Phase 2 — Standalone calculators

### Task 10 — Stamp duty calculator

No case-scoping needed (rates are public/statutory, not case data), no migration.

1. **`app/Services/StampDutyCalculator.php`** — pure calculation service:
   ```php
   class StampDutyCalculator
   {
       public const INSTRUMENTS = [
           'transfer_sale' => 'Deed of Transfer — Sale',
           'gift' => 'Deed of Gift',
           'lease' => 'Lease / Rent Agreement',
           'mortgage' => 'Mortgage Deed',
           'affidavit' => 'Affidavit',
           // extend as needed
       ];

       /**
        * @return array{duty: float, breakdown: array<int, array{label: string, basis: float, rate: string, amount: float}>, effective_rate: float}
        */
       public function calculate(string $instrument, float $baseAmount): array { /* banded logic per instrument */ }
   }
   ```
   Implement banded logic per instrument (e.g. transfer = 3% of first Rs. 100,000 + 4% of the remainder; gift = 3% of first Rs. 50,000 + 2% of remainder; lease = 2% flat on aggregate rent; mortgage = 0.1% flat; affidavit = flat Rs. 50).
2. **Request:** `app/Http/Requests/CalculateStampDutyRequest.php` — `instrument` required, `Rule::in(array_keys(StampDutyCalculator::INSTRUMENTS))`; `amount` required numeric min:0.
3. **Controller:** `app/Http/Controllers/StampDutyController.php` — `index()` (renders the calculator, optionally accepts `?case_id=` for context only, no authorization tie-in needed since it's not case data), `calculate()` (POST, returns the same view with results, or a small JSON fragment if you want it AJAX-driven with Alpine — either is acceptable, prefer plain POST+Blade for consistency with the rest of the app).
4. **Routes:** new file `routes/tools.php`, registered in `routes/web.php` alongside the other `require` lines:
   ```php
   Route::middleware(['auth'])->group(function () {
       Route::get('/tools/stamp-duty', [StampDutyController::class, 'index'])->name('stamp-duty.index');
       Route::post('/tools/stamp-duty', [StampDutyController::class, 'calculate'])->name('stamp-duty.calculate');
   });
   ```
5. **View:** `resources/views/tools/stamp-duty.blade.php` — form (instrument select + amount) + results table (mirror the breakdown table structure from the LankaLaw reference: description / basis / rate / duty rows + total). **Must include a visible disclaimer**: "Reference rates only. Confirm against the current Gazette notification and Inland Revenue / provincial requirements before filing."
6. **Nav/entry points:** link from `documents/create.blade.php` (near deed-of-transfer generation) and add a `Tools` link in `navigation.blade.php`.
7. **Test:** `tests/Unit/StampDutyCalculatorTest.php` — one assertion per instrument against a hand-calculated expected value (e.g. transfer on Rs. 500,000 = 3% × 100,000 + 4% × 400,000 = 3,000 + 16,000 = 19,000).

---

### Task 11 — Inheritance calculator

Scope explicitly to **General Law** and **Muslim Law** only (matches `LegalCase::APPLICABLE_LAWS` values `general` and `muslim`; do not attempt Kandyan/Thesawalamai without a verified rule source — hide those options in the UI with "Not yet supported").

1. **`app/Services/InheritanceCalculatorService.php`**:
   ```php
   class InheritanceCalculatorService
   {
       public function calculateGeneralLaw(array $heirs): array { /* spouse + children intestate split + written reasoning string */ }
       public function calculateMuslimLaw(array $heirs): array { /* fixed Quranic shares for the common cases: spouse, children, parents */ }
   }
   ```
   Return shape: `['shares' => [['heir' => 'Spouse', 'fraction' => '1/2', 'amount' => 500000.0], ...], 'reasoning' => 'string explaining the rule applied']`.
2. **Request:** `app/Http/Requests/CalculateInheritanceRequest.php` — `applicable_law` required `in:general,muslim`, `estate_value` required numeric min:0, `heirs` array describing composition (spouse present bool, number of children, number of parents, etc. — keep the input shape minimal for the common-case scenarios only).
3. **Controller:** `app/Http/Controllers/InheritanceCalculatorController.php` — `index()`, `calculate()`. Accept optional `?case_id=` to prefill from `$case->applicable_law` when the case's category is Family/Succession.
4. **Routes:** add to `routes/tools.php`:
   ```php
   Route::get('/tools/inheritance', [InheritanceCalculatorController::class, 'index'])->name('inheritance-calculator.index');
   Route::post('/tools/inheritance', [InheritanceCalculatorController::class, 'calculate'])->name('inheritance-calculator.calculate');
   ```
5. **View:** `resources/views/tools/inheritance-calculator.blade.php`. Include the written reasoning text so it reads like something a lawyer could paste into client correspondence (this is the whole point per LawMate's version of this feature).
6. **Entry point:** link from `cases/show.blade.php` header area, visible only `@if(in_array($case->applicable_law, ['general', 'muslim']))`.
7. **Test:** `tests/Unit/InheritanceCalculatorServiceTest.php` — textbook scenarios (e.g. General Law: spouse + 2 children on a Rs. 1,000,000 estate; Muslim Law: spouse + son + daughter fixed shares).

---

## Phase 3 — Case productivity

### Task 8 — Judgment library filters + fused keyword/semantic search

No migration (all filterable columns — `court`, `decided_date`, `category` — already exist on `judgments`).

1. **`app/Http/Controllers/JudgmentController.php::index()`** — extend the existing query building (it already does a keyword + semantic ID merge per the current implementation) to also accept and apply:
   - `court` (partial `LIKE` match)
   - `category` (exact, from `Judgment::CATEGORIES`)
   - `decided_from` / `decided_to` (date range on `decided_date`)
   - `cited_act` — see Task 7, same query param, same controller.
   All as `$request->query(...)`, applied via `->when($request->filled(...), ...)`. Keep as GET params so results are shareable/bookmarkable.
2. **`app/Http/Requests/SearchCaseJudgmentsRequest.php`** and **`app/Services/JudgmentSimilaritySearchService::search()`** (case-level related-judgments search) — currently semantic-only. Add a keyword pass in `CaseJudgmentController::search()`:
   ```php
   $semanticResults = $search->search($query)->map(...); // existing
   $keywordMatches = Judgment::query()
       ->where(function ($q) use ($query) {
           $term = '%'.$query.'%';
           $q->where('title', 'LIKE', $term)
             ->orWhere('summary', 'LIKE', $term)
             ->orWhere('cited_acts', 'LIKE', $term);
       })
       ->whereNotIn('id', $semanticResults->pluck('id'))
       ->limit(10)
       ->get();
   ```
   Merge, tagging each row with `'match_type' => 'semantic'|'keyword'` for a UI badge, semantic first.
3. **View:** add a filter form (GET, above the table) to `resources/views/judgments/index.blade.php`; add the `match_type` badge to the related-judgments result cards in `cases/show.blade.php`.
4. **Test:** extend `tests/Feature/JudgmentLibraryTest.php` (filters) and `tests/Feature/CaseRelatedJudgmentsTest.php` (fused search — seed a judgment with no embedding but a matching title, assert it still surfaces via the keyword pass).

---

### Task 16 — Court dates `.ics` export

No migration.

1. **Composer dependency:** add `spatie/icalendar-generator` (`composer require spatie/icalendar-generator`) rather than hand-rolling RFC 5545 — same "add a maintained package" pattern already used for `barryvdh/laravel-dompdf`.
2. **`app/Services/IcsCalendarGenerator.php`** — wraps the package, `generate(Collection $courtDates): string`, one `VEVENT` per court date (title = case display name + type, date = `court_dates.date`).
3. **`app/Http/Controllers/CourtDateController.php`** — add `export()`. Reuse the **exact same scoping** already in `index()` (partner sees all, associate sees only assigned-case dates) — do not re-derive it, extract the existing scoping query into a small private method if it isn't already, and call it from both `index()` and `export()`.
4. **Route:** add to `routes/scheduling.php`:
   ```php
   Route::get('/court-dates/export.ics', [CourtDateController::class, 'export'])->name('court-dates.export');
   ```
   (Add this line **before** `Route::resource(...)` if route-order conflicts arise with `court-dates/{court_date}`.)
5. **Response:** `return response($icsString, 200, ['Content-Type' => 'text/calendar', 'Content-Disposition' => 'attachment; filename="lexlanka-court-dates.ics"']);`
6. **View:** add an "Export Calendar (.ics)" button to `resources/views/court-dates/index.blade.php`.
7. **Test:** `tests/Feature/CourtDateIcsExportTest.php` — assert `Content-Type: text/calendar`, assert the body contains a `VEVENT` for a seeded trial date; associate export excludes another attorney's case dates (authorization-matrix style, matching existing test conventions).

---

### Task 14 — Time entries on a case

**Migrations:**
1. `database/migrations/2026_09_20_100004_create_time_entries_table.php`:
   ```php
   Schema::create('time_entries', function (Blueprint $table) {
       $table->id();
       $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
       $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
       $table->date('date');
       $table->decimal('hours', 5, 2);
       $table->string('description');
       $table->boolean('billable')->default(true);
       $table->foreignId('ledger_entry_id')->nullable()->constrained('ledger_entries')->nullOnDelete();
       $table->timestamps();
   });
   ```
2. `database/migrations/2026_09_20_100005_add_time_entry_id_to_ledger_entries_table.php` — add nullable `time_entry_id` back-reference is **not** needed if the FK above already links `time_entries.ledger_entry_id`; skip this second migration unless you also want to query "which time entries funded this ledger row" from the ledger side. Recommendation: skip it, the one-directional FK from `time_entries` is sufficient.

**Model:** `app/Models/TimeEntry.php` — `belongsTo(LegalCase::class, 'case_id')`, `belongsTo(User::class, 'user_id')`, `belongsTo(LedgerEntry::class, 'ledger_entry_id')`.

**Request:** `app/Http/Requests/StoreTimeEntryRequest.php` — `authorize()`: `Gate::allows('view', $case)` (case resolved from route). `rules()`: `hours` numeric `min:0.1|max:24`, `description` required string max:1000, `date` required date.

**Controller:** `app/Http/Controllers/TimeEntryController.php`:
- `store(StoreTimeEntryRequest $request, LegalCase $case)`
- `destroy(TimeEntry $timeEntry)` — author (`user_id === auth()->id()`) or `Gate::allows('manage-users')`, same pattern as `ResearchNoteController::destroy`.
- `postToLedger(Request $request, TimeEntry $timeEntry)` — requires `Gate::allows('view-financials')`; validates an explicit `amount` (lawyer decides the billed amount, this is not an auto-computed hourly-rate feature since no rate field exists on `User` yet); creates a `LedgerEntry` with `type = operational`, links `time_entries.ledger_entry_id`.

**Routes:** add to `routes/billing.php`, inside the existing `role:partner,associate` group:
```php
Route::post('/cases/{case}/time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');
Route::post('/time-entries/{timeEntry}/post-to-ledger', [TimeEntryController::class, 'postToLedger'])->name('time-entries.post-to-ledger')->middleware('can:view-financials');
```

**View:** new "Time" tab on `cases/show.blade.php` (same Alpine tab pattern as `court_dates`/`documents`/`research`), table of entries + add-entry form; "Post to Ledger" button visible only `@can('view-financials')`.

**Test:** `tests/Feature/TimeEntryTest.php` — CRUD + authorization matrix (owner/partner delete; non-owning associate on another case gets 403); `postToLedger` creates the correct `LedgerEntry` row and links it back.

**CONTRACT.md:** add a `time_entries` table section.

---

### Task 15 — Case tasks

**Migration:** `database/migrations/2026_09_20_100006_create_case_tasks_table.php`:
```php
Schema::create('case_tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
    $table->string('title');
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->date('due_date')->nullable();
    $table->boolean('is_done')->default(false);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();
});
```

**Model:** `app/Models/CaseTask.php` — `belongsTo(LegalCase::class)`, `belongsTo(User::class, 'assigned_to')` as `assignee()`, `belongsTo(User::class, 'created_by')` as `creator()`.

**Request:** `app/Http/Requests/StoreCaseTaskRequest.php` — `authorize()`: `Gate::allows('view', $case)`. `rules()`: `title` required max:255, `assigned_to` nullable `exists:users,id`, `due_date` nullable date.

**Controller:** `app/Http/Controllers/CaseTaskController.php` — `store()`, `update()` (PATCH — toggle `is_done`, edit `title`/`assigned_to`/`due_date`; author or partner), `destroy()` (author or `manage-users`).

**Routes:** new file `routes/tasks.php`, registered in `routes/web.php`:
```php
Route::middleware(['auth'])->group(function () {
    Route::post('/cases/{case}/tasks', [CaseTaskController::class, 'store'])->name('case-tasks.store');
    Route::patch('/case-tasks/{caseTask}', [CaseTaskController::class, 'update'])->name('case-tasks.update');
    Route::delete('/case-tasks/{caseTask}', [CaseTaskController::class, 'destroy'])->name('case-tasks.destroy');
});
```

**View:** "Tasks" tab on `cases/show.blade.php` — checklist (checkbox → PATCH `is_done`), add-task form. Also add a small "My open tasks" widget to `resources/views/dashboard.blade.php` / `DashboardController` — tasks where `assigned_to = auth()->id()` and `is_done = false`, ordered by `due_date` ascending.

**Test:** `tests/Feature/CaseTaskTest.php` — CRUD + authorization matrix; dashboard widget shows only the logged-in user's open tasks.

**CONTRACT.md:** add a `case_tasks` table section.

---

## Phase 4 — Research AI

> These three tasks share one new low-level building block: a **generic Gemini text-completion helper**, because `JudgmentIngestionService`'s HTTP-call methods are `private` and tightly coupled to PDF ingestion. Build the shared piece first.

**Shared prerequisite:** `app/Services/GeminiChatService.php`
```php
class GeminiChatService
{
    private const MODEL = 'gemini-flash-latest'; // same free-tier pool as ingestion — reuse GeminiKeyPool

    public function __construct(private readonly GeminiKeyPool $keyPool) {}

    /** Plain prompt -> plain text completion, with the same 429/retry handling as ingestion. */
    public function complete(string $prompt): string { /* mirrors JudgmentIngestionService's private generateContent-style call, via GeminiKeyPool::run() */ }
}
```
Study `JudgmentIngestionService`'s existing `generateContent`-style private method (around the `API_BASE`, `MAX_TRANSIENT_RETRIES`, `GeminiKeyPool` usage) and factor out the reusable HTTP-call skeleton rather than duplicating it — if it makes sense, extract it into this new service and have `JudgmentIngestionService` delegate to it instead of keeping two copies of the same retry logic.

---

### Task 5 — Grounded research answer on a case ("ask this case's issue")

**Migration:** `database/migrations/2026_09_20_100001_create_case_research_queries_table.php`:
```php
Schema::create('case_research_queries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
    $table->foreignId('asked_by')->constrained('users')->restrictOnDelete();
    $table->text('question');
    $table->text('answer')->nullable();
    $table->json('judgment_ids')->nullable(); // grounding set actually used
    $table->timestamps();
});
```

**Model:** `app/Models/CaseResearchQuery.php` — cast `judgment_ids` to `array`; `belongsTo(LegalCase::class)`, `belongsTo(User::class, 'asked_by')`.

**Service:** `app/Services/CaseResearchAssistantService.php`:
```php
class CaseResearchAssistantService
{
    public function __construct(
        private readonly JudgmentSimilaritySearchService $search,
        private readonly GeminiChatService $chat,
    ) {}

    public function answer(string $question, int $limit = 8): array
    {
        $hits = $this->search->search($question, $limit); // existing service, unchanged

        if ($hits->isEmpty()) {
            return ['answer' => 'No matching judgments found in the firm library for this question.', 'judgment_ids' => []];
        }

        $context = $hits->map(fn ($row) => sprintf(
            "Title: %s\nCourt: %s\nSummary: %s\nCited acts: %s",
            $row['judgment']->title, $row['judgment']->court, $row['judgment']->summary,
            implode('; ', $row['judgment']->cited_acts ?? [])
        ))->implode("\n\n---\n\n");

        $prompt = <<<PROMPT
        You are a legal research assistant. Answer the question below using ONLY the judgments provided in the context. Do not use any outside knowledge or invent citations. If the context does not answer the question, say so explicitly. Cite judgments by their title.

        Context:
        {$context}

        Question: {$question}
        PROMPT;

        return [
            'answer' => $this->chat->complete($prompt),
            'judgment_ids' => $hits->pluck('judgment.id')->all(),
        ];
    }
}
```
The "grounded, no invented citations" instruction is the whole point (mirrors LawMate's stated behavior) — this is a prompt-engineering constraint, not a hard guarantee; log/flag answers for spot-checking initially.

**Request:** `app/Http/Requests/AskCaseResearchQuestionRequest.php` — `authorize()`: `Gate::allows('view', $case)`. `rules()`: `question` required string `min:3|max:2000`.

**Controller:** `app/Http/Controllers/CaseResearchQueryController.php` — `store()` calls the service, persists a `CaseResearchQuery` row, redirects back with the answer in session (same flash pattern as `CaseJudgmentController::search`); `destroy()` — asker or partner.

**Routes:** add to `routes/research.php`:
```php
Route::post('/cases/{case}/research-queries', [CaseResearchQueryController::class, 'store'])->name('cases.research-queries.store');
Route::delete('/cases/{case}/research-queries/{caseResearchQuery}', [CaseResearchQueryController::class, 'destroy'])->name('cases.research-queries.destroy');
```

**View:** new sub-panel inside the existing "Related Judgments" tab (or a new "Research Assistant" tab) on `cases/show.blade.php` — question box, answer display with the grounding judgment titles listed as links, history of past `case_research_queries`, and a "Save as research note" button that pre-fills the `research-notes.store` form with the answer text.

**Test:** `tests/Feature/CaseResearchAssistantTest.php` — `Http::fake()` both the embedding endpoint and the chat-completion endpoint; seed 2-3 judgments with embeddings; assert the returned answer text only references seeded judgment titles present in `judgment_ids`; assert a `case_research_queries` row is created; assert a clerk without access-code verification is redirected, not answered (reuse `EnsuresCaseAccessCode` pattern check).

**CONTRACT.md:** add `case_research_queries` table section.

---

### Task 6 — Ask a specific judgment

**Migration:** `database/migrations/2026_09_20_100002_add_extracted_text_to_judgments_table.php`:
```php
Schema::table('judgments', function (Blueprint $table) {
    $table->longText('extracted_text')->nullable()->after('summary');
});
```

**Ingestion change:** `app/Services/JudgmentIngestionService.php` currently extracts PDF text transiently (via `PdfTextExtractor`) for embedding/summarization but doesn't persist it. Modify the per-judgment save step to also store `extracted_text` on the `Judgment` row. This is additive — do not change the embedding/summarization prompts.

**Fallback for existing/dataset rows without `extracted_text`:** dataset-imported judgments (`JudgmentDatasetImportService`) and any judgment ingested before this change will have `extracted_text = null`. The Q&A service must fall back to `summary` + `cited_acts` as context and the UI must show a note: *"Full text not indexed for this record — answer is based on the summary only."*

**Service:** `app/Services/JudgmentQaService.php`:
```php
class JudgmentQaService
{
    public function __construct(private readonly GeminiChatService $chat) {}

    public function ask(Judgment $judgment, string $question): array
    {
        $context = $judgment->extracted_text
            ?: ($judgment->summary . "\nCited acts: " . implode('; ', $judgment->cited_acts ?? []));

        $grounded = filled($judgment->extracted_text);

        $prompt = <<<PROMPT
        Answer the question using ONLY the judgment text below. If the answer is not in the text, say so.

        Judgment: {$judgment->title}

        Text:
        {$context}

        Question: {$question}
        PROMPT;

        return ['answer' => $this->chat->complete($prompt), 'grounded_in_full_text' => $grounded];
    }
}
```

**Request:** `app/Http/Requests/AskJudgmentQuestionRequest.php` — no case gating (judgments are firm-wide per `CONTRACT.md`, "do not gate with `CaseAccessPolicy`"), just `auth`. `rules()`: `question` required string `min:3|max:2000`.

**Controller:** add `ask(AskJudgmentQuestionRequest $request, Judgment $judgment)` to `JudgmentController` (or a small dedicated `JudgmentQaController` if `JudgmentController` is already large — check its current line count first and split if it's unwieldy).

**Route:** add to `routes/judgments.php`:
```php
Route::post('/judgments/{judgment}/ask', [JudgmentController::class, 'ask'])->name('judgments.ask');
```

**View:** expandable Q&A panel per row on `resources/views/judgments/index.blade.php` (there is no `judgments.show` route currently — keep this inline on the index rather than introducing a new page, unless the index is already too dense, in which case add a minimal `judgments.show` route+view first).

**Test:** `tests/Feature/JudgmentQaTest.php` — `Http::fake()` the chat completion; one case with `extracted_text` set (assert `grounded_in_full_text === true`), one dataset-style row without it (assert `false` and that summary-based context was actually used — you can assert this via the faked request body).

---

### Task 7 — Act/section → citing judgments lookup

No new table. `judgments.cited_acts` is already a JSON array of free-text strings — treat exact-string matching as the MVP, not full statute parsing.

1. **`app/Http/Controllers/JudgmentController.php::index()`** — accept `cited_act` query param (same param handled in Task 8's filter work — implement both in the same PR if convenient, they touch the same method):
   ```php
   $citedAct = $request->query('cited_act');
   $judgments->when($citedAct, function ($q) use ($citedAct) {
       $q->whereRaw('cited_acts LIKE ?', ['%'.$citedAct.'%']); // JSON column stored as text; LIKE tolerates near-matches better than whereJsonContains' exact-match requirement
   });
   ```
2. **UI — make cited acts clickable.** Both places cited acts currently render as plain text need to become links to `route('judgments.index', ['cited_act' => $act])`:
   - `resources/views/cases/show.blade.php` (~lines 500-502 and ~551-553, the "Cited:" lines in the related-judgments search results and attached-judgments table).
   - `resources/views/judgments/index.blade.php` wherever `cited_acts` is rendered per row.
3. **Result page banner:** when `cited_act` is present in the request, show "Showing judgments citing: {act}" with a clear/back link, on `judgments/index.blade.php`.
4. **Test:** extend `tests/Feature/JudgmentLibraryTest.php` — seed two judgments, one with `cited_acts` containing `"Prescription Ordinance No. 22 of 1871"`, filter by a substring of that string, assert only the matching judgment is returned.

---

## Phase 5 — Drafting

### Task 12 — AI-assisted fill of existing PDF templates (proxy, affidavit, deed of transfer)

**Do not change the existing one-click path.** `GET /documents/generate-pdf/{case}/{type}` must keep working exactly as it does today (direct download, no extra input) — this task adds an **optional** richer path in front of it.

1. **Service:** `app/Services/DocumentDraftAssistant.php`:
   ```php
   class DocumentDraftAssistant
   {
       public function __construct(private readonly GeminiChatService $chat) {}

       /** @return array<string,string> suggested field values, keyed per template's narrative placeholders */
       public function suggestFields(LegalCase $case, string $type): array { /* prompt Gemini with case category/summary/applicable_law, ask for the narrative blocks specific to $type */ }
   }
   ```
   Define per-type placeholder keys explicitly (e.g. `affidavit` needs a `grounds` paragraph; `proxy` needs `scope_of_authority`; `deed_of_transfer` needs `consideration_narrative`). Keep this a short, fixed list per type — do not make it open-ended.
2. **Controller (`DocumentController`)** — add:
   - `draftForm(LegalCase $case, string $type)` — GET, renders an editable form pre-filled with case/client data **and** the AI-suggested narrative fields (call the service; if it fails/quota-errors, fall back silently to blank fields — never block the form on AI availability).
   - Modify `generatePdf()` to also accept the extra narrative fields via POST (when arriving from the draft form) and pass them into the `pdf.*` views as additional optional variables, each with a sensible default (empty string / existing placeholder text) so the original direct-download route still renders correctly when those variables are absent.
3. **Blade templates:** `pdf/affidavit.blade.php`, `pdf/proxy.blade.php`, `pdf/deed-of-transfer.blade.php` — add the new optional variables (`{{ $grounds ?? '' }}` etc.) at the appropriate narrative section, without disturbing the existing structure.
4. **Route:** add to `routes/documents.php`:
   ```php
   Route::get('/documents/generate/{case}/{type}/draft', [DocumentController::class, 'draftForm'])->name('documents.generate-draft');
   ```
   Keep `GET /documents/generate-pdf/{case}/{type}` (`documents.generate-pdf`) unchanged for backward compatibility; `generatePdf()` should accept `POST` in addition to its current method if the draft form submits there — check current route method and adjust to accept both, or add a distinct POST route (`documents.generate-pdf.submit`) if simpler.
5. **UI entry point:** on `documents/create.blade.php` (or wherever the 3 existing "Generate PDF" buttons live today), add a secondary "Draft with AI" link next to each that goes to `documents.generate-draft` instead of the direct download.
6. **Test:** `tests/Feature/DocumentDraftAssistantTest.php` — `Http::fake()` the chat completion; assert `draftForm` renders with suggested fields populated; assert `generatePdf` with custom narrative fields produces a PDF containing that text (dompdf output — assert on the rendered Blade view's HTML before PDF conversion if that's easier to test, following whatever pattern existing PDF-generation tests use, if any exist — check first).

---

### Task 13 — Three additional document templates

No migration, no AI dependency (can ship independently of Task 12, but do it after so the "Draft with AI" entry point pattern already exists and these can plug into it too).

1. **New Blade views**, matching the structure/styling of `resources/views/pdf/affidavit.blade.php`:
   - `resources/views/pdf/letter-of-demand.blade.php`
   - `resources/views/pdf/motion-fix-trial-date.blade.php` — needs the case's court dates passed in (nearest upcoming date as the default target).
   - `resources/views/pdf/replication-rejoinder.blade.php`
2. **`DocumentController::generatePdf()`** — extend the three parallel arrays:
   ```php
   $allowedTypes = ['proxy', 'affidavit', 'deed_of_transfer', 'letter_of_demand', 'motion_fix_trial_date', 'replication_rejoinder'];
   $viewMap = [..., 'letter_of_demand' => 'pdf.letter-of-demand', 'motion_fix_trial_date' => 'pdf.motion-fix-trial-date', 'replication_rejoinder' => 'pdf.replication-rejoinder'];
   $titleMap = [..., 'letter_of_demand' => 'Letter of Demand', 'motion_fix_trial_date' => 'Motion to Fix Trial Date', 'replication_rejoinder' => 'Replication / Rejoinder'];
   ```
   For `motion_fix_trial_date`, also load `$case->courtDates` (already eager-loadable) and pass the nearest upcoming trial/calling date into the view.
3. **UI:** add the 3 new buttons alongside the existing 3 in `documents/create.blade.php`.
4. **Test:** extend whatever feature test currently covers `documents.generate-pdf` for the existing 3 types (find it first — likely in `tests/Feature/` under a documents-related test file) with 3 more cases asserting `200` + correct `Content-Disposition` filename per new type.

---

## Phase 6 — Citation graph

### Task 9 — Cited-by / "still followed in this library" tracking

**Scope discipline:** this only ever reasons about judgments **already in this firm's library** — it is not a national overrule-detection service (that would require a licensed national case-law corpus, which this system deliberately does not have).

**Migration:** `database/migrations/2026_09_20_100003_create_judgment_citations_table.php`:
```php
Schema::create('judgment_citations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('citing_judgment_id')->constrained('judgments')->cascadeOnDelete();
    $table->foreignId('cited_judgment_id')->nullable()->constrained('judgments')->nullOnDelete(); // null = referenced case not (yet) in this library
    $table->string('cited_case_text'); // raw citation text as extracted, always kept even if unresolved
    $table->enum('relationship', ['cites', 'followed', 'distinguished', 'overruled'])->default('cites');
    $table->timestamps();

    $table->unique(['citing_judgment_id', 'cited_case_text']);
});
```

**Model:** `app/Models/JudgmentCitation.php` — `belongsTo(Judgment::class, 'citing_judgment_id')`, `belongsTo(Judgment::class, 'cited_judgment_id')`.

**Ingestion schema extension:** extend the existing Gemini structured-output schema in `JudgmentIngestionService` (the same JSON-schema pattern already used for `cited_acts` in `detectCompilation`/summarization) to also return:
```json
"cited_cases": [{"citation": "string", "relationship": "cites|followed|distinguished|overruled"}]
```
This is a **prompt and schema change only** — do not touch the detection/summarization control flow beyond adding this field to the requested JSON shape and reading it back.

**Linking service:** `app/Services/JudgmentCitationLinker.php`:
```php
class JudgmentCitationLinker
{
    /** Called after a Judgment is saved with its raw cited_cases list. */
    public function link(Judgment $judgment, array $citedCases): void
    {
        foreach ($citedCases as $entry) {
            $resolved = Judgment::where('id', '!=', $judgment->id)
                ->where('title', 'LIKE', '%'.$entry['citation'].'%') // best-effort fuzzy resolve; leave unresolved (null) if no match
                ->first();

            JudgmentCitation::updateOrCreate(
                ['citing_judgment_id' => $judgment->id, 'cited_case_text' => $entry['citation']],
                ['cited_judgment_id' => $resolved?->id, 'relationship' => $entry['relationship'] ?? 'cites']
            );
        }
    }
}
```
Wire this into `JudgmentIngestionService`'s save step, right after a judgment (or each item in a compilation) is persisted.

**UI:** on a judgment's row/detail (library index and/or the attached-judgment view in `cases/show.blade.php`):
- "Cites" — `JudgmentCitation::where('citing_judgment_id', $judgment->id)->get()`, shown with relationship badges and linked to `cited_judgment_id` when resolved.
- "Cited by" (the reverse — this is the useful "is this still good law within our own library" signal) — `JudgmentCitation::where('cited_judgment_id', $judgment->id)->get()`.

**Test:** `tests/Feature/JudgmentCitationLinkerTest.php` — seed Judgment A ("Perera v. Silva"), ingest/create Judgment B whose mocked Gemini response includes `cited_cases: [{citation: "Perera v. Silva", relationship: "overruled"}]`, assert a `judgment_citations` row links B→A with `relationship = overruled`, and that A's "cited by" list shows B.

**CONTRACT.md:** add `judgment_citations` table section, and document the `cited_cases` addition to the ingestion JSON schema.

---

## Cross-cutting checklist (apply to every task before merging)

- [ ] Migration runs cleanly on a fresh `php artisan migrate:fresh --seed`.
- [ ] `CaseAccessPolicy::view` (or the relevant existing Gate) is the authorization mechanism — no new ad-hoc permission logic invented.
- [ ] Feature test covers at least: happy path, one negative-authorization case (wrong role or non-owned case), and one validation-failure case.
- [ ] Any Gemini call in a test uses `Http::fake()` — no live API calls in the suite.
- [ ] `CONTRACT.md` updated if schema/Gates/routes files changed.
- [ ] `composer test` passes in full (not just the new test file) — some of these tasks touch shared files (`LegalCaseController`, `cases/show.blade.php`, `routes/billing.php`, `JudgmentController`) that already have coverage.
- [ ] New nav links / buttons respect existing role visibility conventions (`@can`, `@if(auth()->user()->role === ...)`) rather than being shown to everyone by default.
