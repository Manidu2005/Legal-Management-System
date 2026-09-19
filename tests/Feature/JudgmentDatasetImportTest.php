<?php

namespace Tests\Feature;

use App\Models\Judgment;
use App\Models\User;
use App\Services\JudgmentDatasetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JudgmentDatasetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-gemini-key']);
        Cache::flush();
    }

    public function test_navod_and_nuuuwan_rows_are_imported_embedded_and_searchable(): void
    {
        $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'datasets-server.huggingface.co/rows')) {
                if (str_contains($url, 'sri-lanka-case-law')) {
                    return Http::response([
                        'num_rows_total' => 1,
                        'rows' => [[
                            'row' => [
                                'source' => 'SLR',
                                'case_no' => '01 SLR 001',
                                'court' => 'Supreme Court',
                                'case_title' => 'Perera v. Silva',
                                'short_summary' => 'Adverse possession established.',
                                'full_summary' => 'The Court held that adverse possession was established on the facts.',
                                'decided_date' => '1999-05-12',
                                'laws_referred' => [[
                                    'law' => 'Prescription Ordinance',
                                    'chapter_or_section' => 's.3',
                                    'explanation' => 'Limitation.',
                                ]],
                            ],
                        ]],
                    ], 200);
                }

                if (str_contains($url, 'appeal-court')) {
                    return Http::response([
                        'num_rows_total' => 1,
                        'rows' => [[
                            'row' => [
                                'doc_type' => 'lk_appeal_court_judgements',
                                'doc_id' => '2020-01-01-CA-Writ-1-2020',
                                'num' => 'CA Writ-1-2020',
                                'date_str' => '2020-01-01',
                                'description' => 'Land acquisition writ',
                                'url_metadata' => 'https://courtofappeal.lk/example',
                                'url_pdf' => 'https://courtofappeal.lk/example.pdf',
                                'parties' => 'Ranasinghe vs Divisional Secretary',
                                'judgement_by' => 'Hon. Judge',
                                'keywords' => 'Land Acquisition',
                                'legistation' => 'Land Acquisition Act No. 09 of 1950',
                            ],
                        ]],
                    ], 200);
                }

                return Http::response([
                    'num_rows_total' => 1,
                    'rows' => [[
                        'row' => [
                            'doc_type' => 'lk_supreme_court_judgements',
                            'doc_id' => '2026-05-07-SC-APPEAL-131-2013',
                            'num' => 'SC/APPEAL/131/2013',
                            'date_str' => '2026-05-07',
                            'description' => 'Labour appeal',
                            'url_metadata' => 'https://supremecourt.lk/judgements/',
                            'url_pdf' => 'https://supremecourt.lk/example.pdf',
                            'parties' => 'Gintota Plywood Manufacturers (Pvt) Limited vs Commissioner of Labour',
                            'judgement_by' => 'Hon. Justice A.H.M.D.Nawaz',
                        ],
                    ]],
                ], 200);
            }

            if (str_contains($url, 'embedContent')) {
                return Http::response(['embedding' => ['values' => [0.1, 0.2, 0.3]]], 200);
            }

            return Http::response(['error' => 'unexpected '.$url], 500);
        });

        $this->actingAs($partner)
            ->post(route('judgments.datasets.import'))
            ->assertRedirect(route('judgments.index'));

        $this->assertSame(3, Judgment::count());

        $navod = Judgment::query()->where('external_id', 'navod:SLR:01 SLR 001')->first();
        $this->assertNotNull($navod);
        $this->assertSame('Perera v. Silva', $navod->title);
        $this->assertSame(['Prescription Ordinance s.3'], $navod->cited_acts);
        $this->assertNotEmpty($navod->embedding);
        $this->assertSame('navod_sri_lanka_case_law', $navod->source_dataset);

        $appeal = Judgment::query()->where('source_dataset', 'nuuuwan_appeal_court')->first();
        $this->assertNotNull($appeal);
        $this->assertSame('https://courtofappeal.lk/example.pdf', $appeal->source_url);
        $this->assertNotEmpty($appeal->embedding);

        $supreme = Judgment::query()->where('source_dataset', 'nuuuwan_supreme_court')->first();
        $this->assertNotNull($supreme);
        $this->assertStringContainsString('SC/APPEAL/131/2013', (string) $supreme->title);

        $this->actingAs($partner)
            ->post(route('judgments.datasets.import'))
            ->assertRedirect(route('judgments.index'));

        $this->assertSame(3, Judgment::count());

        $this->actingAs($partner)
            ->get(route('judgments.download', $appeal))
            ->assertRedirect('https://courtofappeal.lk/example.pdf');

        $this->actingAs($partner)
            ->get(route('judgments.index', ['q' => 'Prescription Ordinance']))
            ->assertOk()
            ->assertSee('Perera v. Silva');

        $this->actingAs($partner)
            ->get(route('search.index', ['q' => 'Perera']))
            ->assertOk()
            ->assertSee('Perera v. Silva');
    }

    public function test_clerk_cannot_import_datasets(): void
    {
        $clerk = User::factory()->create(['role' => 'clerk', 'status' => 'active']);

        $this->actingAs($clerk)
            ->post(route('judgments.datasets.import'))
            ->assertForbidden();
    }

    public function test_embed_pending_fills_missing_vectors(): void
    {
        $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        Judgment::create([
            'title' => 'Pending Embed Case',
            'category' => 'judgment',
            'pdf_path' => '',
            'summary' => 'A mortgage of movables.',
            'uploaded_by' => $partner->id,
            'external_id' => 'navod:CLR:01 CLR 001',
            'source_dataset' => 'navod_sri_lanka_case_law',
            'source_hash' => hash('sha256', 'navod:CLR:01 CLR 001'),
            'source_start_page' => 1,
            'source_end_page' => 1,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [0.4, 0.5, 0.6]],
            ], 200),
        ]);

        app(JudgmentDatasetImportService::class)->embedPending(10);

        $this->assertNotEmpty(Judgment::query()->first()->embedding);
    }
}
