<?php

namespace Tests\Feature;

use App\Models\CaseCategory;
use App\Models\CaseJudgment;
use App\Models\Client;
use App\Models\Judgment;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\CaseCategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaseRelatedJudgmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_similarity_search_returns_judgments_in_expected_order(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);
        $this->seed(CaseCategorySeeder::class);

        $partner = User::factory()->create([
            'role' => 'partner',
            'status' => 'active',
        ]);

        $client = Client::create([
            'name' => 'Test Client',
            'nic' => '199012345678',
            'intake_date' => now()->toDateString(),
        ]);

        $case = LegalCase::create([
            'client_id' => $client->id,
            'assigned_attorney_id' => $partner->id,
            'case_category_id' => CaseCategory::where('name', 'Declaration of title')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $exactSummary = 'In a dispute regarding Kandyan intestate succession, the plaintiff claimed an undivided share of his maternal grandfather\'s property through his mother, who had contracted a deega marriage and later gave birth to him out of wedlock. The court examined the legal effect of that marriage on succession rights in considerable detail.';

        $exact = Judgment::create([
            'title' => 'Exact Match Judgment',
            'category' => 'judgment',
            'court' => 'Supreme Court',
            'decided_date' => '2006-01-15',
            'pdf_path' => 'judgments/exact.pdf',
            'summary' => $exactSummary,
            'cited_acts' => ['Prevention of Frauds Ordinance, Section 6', 'Deceased Estates Succession Act, Section 7'],
            'source_dataset' => 'navod_sri_lanka_case_law',
            'source_url' => 'https://example.test/judgment',
            'embedding' => [1.0, 0.0, 0.0],
            'uploaded_by' => $partner->id,
        ]);

        $partial = Judgment::create([
            'title' => 'Partial Match Judgment',
            'category' => 'judgment',
            'court' => 'Court of Appeal',
            'pdf_path' => 'judgments/partial.pdf',
            'summary' => 'Somewhat related.',
            'embedding' => [0.8, 0.2, 0.0],
            'uploaded_by' => $partner->id,
        ]);

        $orthogonal = Judgment::create([
            'title' => 'Unrelated Judgment',
            'category' => 'other',
            'court' => 'District Court',
            'pdf_path' => 'judgments/unrelated.pdf',
            'summary' => 'Different topic.',
            'embedding' => [0.0, 1.0, 0.0],
            'uploaded_by' => $partner->id,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent' => Http::response([
                'embedding' => [
                    'values' => [1.0, 0.0, 0.0],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($partner)->post(
            route('cases.related-judgments.search', $case),
            ['query' => 'adverse possession of rural land']
        );

        $response->assertRedirect(route('cases.show', $case).'#related-judgments');
        $response->assertSessionHas('judgmentSearchResults');

        $results = session('judgmentSearchResults');
        $this->assertCount(3, $results);
        $this->assertSame($exact->id, $results[0]['id']);
        $this->assertSame($partial->id, $results[1]['id']);
        $this->assertSame($orthogonal->id, $results[2]['id']);
        $this->assertGreaterThan($results[2]['similarity'], $results[1]['similarity']);
        $this->assertEqualsWithDelta(1.0, $results[0]['similarity'], 0.0001);
        $this->assertSame($exactSummary, $results[0]['summary']);
        $this->assertSame('15 Jan 2006', $results[0]['decided_date']);
        $this->assertSame('Sri Lanka Case Law Dataset', $results[0]['source_label']);
        $this->assertTrue($results[0]['has_pdf']);
        $this->assertSame(
            ['Prevention of Frauds Ordinance, Section 6', 'Deceased Estates Succession Act, Section 7'],
            $results[0]['cited_acts']
        );
    }

    public function test_search_results_page_includes_see_more_full_judgment_view(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);
        $this->seed(CaseCategorySeeder::class);

        $partner = User::factory()->create([
            'role' => 'partner',
            'status' => 'active',
        ]);

        $client = Client::create([
            'name' => 'Test Client',
            'nic' => '199012345678',
            'intake_date' => now()->toDateString(),
        ]);

        $case = LegalCase::create([
            'client_id' => $client->id,
            'assigned_attorney_id' => $partner->id,
            'case_category_id' => CaseCategory::where('name', 'Declaration of title')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $summary = 'In a dispute regarding Kandyan intestate succession, the plaintiff claimed an undivided share of his maternal grandfather\'s property through his mother, who had contracted a deega marriage and later gave birth to him out of wedlock. The court examined the legal effect of that marriage on succession rights in considerable detail.';

        Judgment::create([
            'title' => '[2005] LKSC 17; (2006) 1 Sri LR 246',
            'category' => 'judgment',
            'court' => 'Supreme Court',
            'decided_date' => '2006-01-15',
            'pdf_path' => 'judgments/lksc.pdf',
            'summary' => $summary,
            'cited_acts' => ['Prevention of Frauds Ordinance, Section 6'],
            'embedding' => [1.0, 0.0, 0.0],
            'uploaded_by' => $partner->id,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent' => Http::response([
                'embedding' => [
                    'values' => [1.0, 0.0, 0.0],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($partner)
            ->followingRedirects()
            ->post(route('cases.related-judgments.search', $case), [
                'query' => 'divorce widowed',
            ]);

        $response->assertOk();
        $response->assertSee('See more');
        $response->assertSee('Full judgment details');
        $response->assertSee($summary);
        $response->assertSee('15 Jan 2006');
        $response->assertSee('Prevention of Frauds Ordinance, Section 6');
        $response->assertSee('Download PDF');
    }

    public function test_attaching_a_judgment_respects_case_access_policy(): void
    {
        $this->seed(DatabaseSeeder::class);

        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $ownCase = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $judgment = Judgment::create([
            'title' => 'Perera v. Silva',
            'category' => 'judgment',
            'pdf_path' => 'judgments/perera.pdf',
            'summary' => 'Sample holding.',
            'embedding' => [0.1, 0.2, 0.3],
            'uploaded_by' => $associate->id,
        ]);

        $this->actingAs($associate)
            ->post(route('cases.related-judgments.store', $otherCase), [
                'judgment_id' => $judgment->id,
                'relevance_note' => 'Should not attach',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('case_judgment', [
            'legal_case_id' => $otherCase->id,
            'judgment_id' => $judgment->id,
        ]);

        $this->actingAs($associate)
            ->post(route('cases.related-judgments.store', $ownCase), [
                'judgment_id' => $judgment->id,
                'relevance_note' => 'On point for adverse possession',
            ])
            ->assertRedirect(route('cases.show', $ownCase).'#related-judgments');

        $this->assertDatabaseHas('case_judgment', [
            'legal_case_id' => $ownCase->id,
            'judgment_id' => $judgment->id,
            'added_by' => $associate->id,
            'relevance_note' => 'On point for adverse possession',
        ]);

        $this->assertSame(1, CaseJudgment::count());
    }
}
