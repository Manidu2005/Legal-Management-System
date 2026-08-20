<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LegalCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAccessVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_associate_only_sees_own_cases_in_index(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCaseIds = LegalCase::where('assigned_attorney_id', $associate->id)->pluck('id');
        $otherCaseIds = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->pluck('id');

        $response = $this->actingAs($associate)->get('/cases');
        $response->assertOk();

        $cases = $response->viewData('cases');
        $returnedIds = collect($cases->items())->pluck('id');

        $this->assertTrue($returnedIds->diff($ownCaseIds)->isEmpty(), 'Associate index leaked non-owned cases.');
        foreach ($otherCaseIds as $id) {
            $this->assertFalse($returnedIds->contains($id));
        }
    }

    public function test_partner_sees_all_cases_in_index(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $totalCases = LegalCase::count();

        $response = $this->actingAs($partner)->get('/cases');
        $response->assertOk();

        $cases = $response->viewData('cases');
        $this->assertEquals(min($totalCases, 15), $cases->count());
        $this->assertEquals($totalCases, $cases->total());
    }

    public function test_associate_cannot_show_unowned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->get("/cases/{$otherCase->id}");
        $response->assertForbidden();
    }

    public function test_associate_can_show_owned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCase = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->get("/cases/{$ownCase->id}");
        $response->assertOk();
    }

    public function test_associate_cannot_edit_unowned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->get("/cases/{$otherCase->id}/edit");
        $response->assertForbidden();
    }

    public function test_associate_document_index_scoped_to_own_cases(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCaseIds = LegalCase::where('assigned_attorney_id', $associate->id)->pluck('id');

        $response = $this->actingAs($associate)->get('/documents');
        $response->assertOk();

        $documents = $response->viewData('documents');
        foreach ($documents->items() as $document) {
            $this->assertTrue($ownCaseIds->contains($document->case_id));
        }
    }

    public function test_associate_cannot_view_document_of_unowned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();
        $document = $otherCase->documents()->first();

        if (! $document) {
            $this->markTestSkipped('No document found for an unowned case in seed data.');
        }

        $response = $this->actingAs($associate)->get("/documents/{$document->id}");
        $response->assertForbidden();
    }

    public function test_associate_cannot_view_billing_of_unowned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->get("/billing/case/{$otherCase->id}");
        $response->assertForbidden();
    }

    public function test_associate_can_view_billing_of_owned_case(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCase = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();

        $response = $this->actingAs($associate)->get("/billing/case/{$ownCase->id}");
        $response->assertOk();
    }

    public function test_partner_can_view_billing_of_any_case(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $anyCase = LegalCase::where('assigned_attorney_id', '!=', $partner->id)->firstOrFail();

        $response = $this->actingAs($partner)->get("/billing/case/{$anyCase->id}");
        $response->assertOk();
    }
}
