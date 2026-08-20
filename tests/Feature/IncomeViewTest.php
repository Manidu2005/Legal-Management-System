<?php

namespace Tests\Feature;

use App\Models\LegalCase;
use App\Models\LedgerEntry;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ---------------------------------------------------------------
    // Billing index scoping
    // ---------------------------------------------------------------

    public function test_associate_billing_index_only_shows_own_cases(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCaseIds = LegalCase::where('assigned_attorney_id', $associate->id)->pluck('id');

        $response = $this->actingAs($associate)->get('/billing');
        $response->assertOk();

        $caseSummaries = $response->viewData('caseSummaries');
        $returnedIds = collect($caseSummaries)->map(fn ($s) => $s['case']->id);

        $this->assertEqualsCanonicalizing($ownCaseIds->all(), $returnedIds->all());
    }

    public function test_partner_billing_index_shows_all_cases(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $allCaseIds = LegalCase::pluck('id');

        $response = $this->actingAs($partner)->get('/billing');
        $response->assertOk();

        $caseSummaries = $response->viewData('caseSummaries');
        $returnedIds = collect($caseSummaries)->map(fn ($s) => $s['case']->id);

        $this->assertEqualsCanonicalizing($allCaseIds->all(), $returnedIds->all());
    }

    public function test_associate_billing_index_includes_income_summary(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($associate)->get('/billing');
        $response->assertOk();

        $incomeSummary = $response->viewData('incomeSummary');

        $this->assertNotNull($incomeSummary);
        $this->assertArrayHasKey('rate', $incomeSummary);
        $this->assertArrayHasKey('case_summaries', $incomeSummary);
        $this->assertArrayHasKey('total_income', $incomeSummary);
        $this->assertEquals(8000.00, $incomeSummary['rate']);
    }

    public function test_partner_billing_index_has_no_income_summary(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($partner)->get('/billing');
        $response->assertOk();

        $this->assertNull($response->viewData('incomeSummary'));
    }

    // ---------------------------------------------------------------
    // My Income route removed
    // ---------------------------------------------------------------

    public function test_my_income_route_no_longer_exists(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($associate)->get('/my-income');

        // Route removed — should 404 (or 405 if method not allowed)
        $this->assertTrue(in_array($response->status(), [404, 405]));
    }

    // ---------------------------------------------------------------
    // Create Invoice scoping
    // ---------------------------------------------------------------

    public function test_associate_create_invoice_only_shows_own_cases(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCaseIds = LegalCase::where('assigned_attorney_id', $associate->id)
            ->where('status', '!=', 'case_closed')
            ->pluck('id');

        $response = $this->actingAs($associate)->get('/billing/create-invoice');
        $response->assertOk();

        $cases = $response->viewData('cases');
        $returnedIds = $cases->pluck('id');

        $this->assertEqualsCanonicalizing($ownCaseIds->all(), $returnedIds->all());
    }

    public function test_associate_cannot_generate_invoice_for_other_attorneys_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->post('/billing/generate-invoice', [
            'case_id' => $otherCase->id,
        ]);

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Ledger entry authorization
    // ---------------------------------------------------------------

    public function test_associate_cannot_create_ledger_entry_on_other_attorneys_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->post('/ledger-entries', [
            'case_id' => $otherCase->id,
            'type' => 'operational',
            'amount' => 1000.00,
            'description' => 'Unauthorized entry',
        ]);

        $response->assertForbidden();
    }

    public function test_associate_cannot_delete_ledger_entry_on_other_attorneys_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $entry = LedgerEntry::create([
            'case_id' => $otherCase->id,
            'type' => 'operational',
            'amount' => 500.00,
            'description' => 'Test entry',
            'recorded_by' => $otherCase->assigned_attorney_id,
        ]);

        $response = $this->actingAs($associate)->delete("/ledger-entries/{$entry->id}");

        $response->assertForbidden();
    }

    public function test_partner_can_create_ledger_entry_on_any_case(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $anyCase = LegalCase::firstOrFail();

        $response = $this->actingAs($partner)->post('/ledger-entries', [
            'case_id' => $anyCase->id,
            'type' => 'operational',
            'amount' => 2000.00,
            'description' => 'Partner ledger entry',
        ]);

        $response->assertRedirect(route('billing.case', $anyCase->id));
    }

    public function test_associate_can_create_ledger_entry_on_own_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCase = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->post('/ledger-entries', [
            'case_id' => $ownCase->id,
            'type' => 'operational',
            'amount' => 1500.00,
            'description' => 'Associate own case entry',
        ]);

        $response->assertRedirect(route('billing.case', $ownCase->id));
    }

    // ---------------------------------------------------------------
    // Firm income — unchanged behavior
    // ---------------------------------------------------------------

    public function test_partner_can_view_firm_income(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($partner)->get('/firm-income');

        $response->assertOk();
    }

    public function test_associate_cannot_view_firm_income_route(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($associate)->get('/firm-income');

        $response->assertForbidden();
    }

    public function test_clerk_cannot_view_firm_income_route(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($clerk)->get('/firm-income');

        $response->assertForbidden();
    }

    public function test_firm_income_breaks_down_per_attorney_and_sums_to_grand_total(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($partner)->get('/firm-income');
        $response->assertOk();

        $attorneySummaries = $response->viewData('attorneySummaries');
        $grandTotal = $response->viewData('grandTotal');

        $this->assertGreaterThan(0, $attorneySummaries->count());

        $sumOfSubtotals = $attorneySummaries->sum('total_income');
        $this->assertEquals($sumOfSubtotals, $grandTotal);

        // Every attorney listed must actually have at least one case
        // (getFirmIncomeSummary filters out attorneys with none).
        foreach ($attorneySummaries as $summary) {
            $this->assertNotEmpty($summary['case_summaries']);
        }
    }
}
