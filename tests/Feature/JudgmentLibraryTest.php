<?php

namespace Tests\Feature;

use App\Models\Judgment;
use App\Models\JudgmentImportBatch;
use App\Models\JudgmentImportItem;
use App\Models\User;
use App\Services\PdfPageSlicer;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JudgmentLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['services.gemini.key' => 'test-gemini-key']);
    }

    public function test_single_judgment_upload_embeds_extracted_text_and_stores_cited_acts(): void
    {
        $user = User::factory()->create([
            'role' => 'clerk',
            'status' => 'active',
        ]);

        $extractedText = implode(' ', array_fill(0, 120, 'possession'));
        $embeddingVector = [0.5, 0.5, 0.0, 0.0];
        $citedActs = ['Prescription Ordinance s.3', 'Civil Procedure Code s.154'];
        $summary = 'The Court held that adverse possession was established.';

        $this->mock(PdfTextExtractor::class, function ($mock) use ($extractedText) {
            $mock->shouldReceive('extractFromPath')
                ->once()
                ->andReturn($extractedText);
        });

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'is_compilation' => false,
                                'cases' => [[
                                    'citation' => 'Perera v. Silva',
                                    'court' => 'Supreme Court of Sri Lanka',
                                    'decided_date' => '2020-05-12',
                                    'start_page' => 1,
                                    'end_page' => 40,
                                    'cited_acts' => $citedActs,
                                ]],
                            ]),
                        ]]],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'citation' => 'Perera v. Silva',
                                'court' => 'Supreme Court of Sri Lanka',
                                'decided_date' => '2020-05-12',
                                'summary' => $summary,
                                'cited_acts' => $citedActs,
                            ]),
                        ]]],
                    ]],
                ], 200),
            'generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent' => Http::response([
                'embedding' => ['values' => $embeddingVector],
            ], 200),
        ]);

        $pdf = UploadedFile::fake()->create('perera-v-silva.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf,
        ]);

        $response->assertRedirect(route('judgments.index'));

        $judgment = Judgment::query()->first();
        $this->assertNotNull($judgment);
        $this->assertSame('Perera v. Silva', $judgment->title);
        $this->assertSame($summary, $judgment->summary);
        $this->assertSame($citedActs, $judgment->cited_acts);
        $this->assertNotEmpty($judgment->embedding);
        $this->assertNotNull($judgment->source_hash);
    }

    public function test_compilation_confirm_indexes_slices_and_skips_duplicates_on_resume(): void
    {
        $user = User::factory()->create([
            'role' => 'partner',
            'status' => 'active',
        ]);

        $cases = [
            [
                'citation' => 'Case Alpha v. Beta',
                'court' => 'Supreme Court',
                'decided_date' => '2018-01-01',
                'start_page' => 1,
                'end_page' => 5,
                'cited_acts' => ['Act A s.1'],
            ],
            [
                'citation' => 'Case Gamma v. Delta',
                'court' => 'Court of Appeal',
                'decided_date' => '2019-02-02',
                'start_page' => 6,
                'end_page' => 12,
                'cited_acts' => ['Act B s.2'],
            ],
            [
                'citation' => 'Case Epsilon v. Zeta',
                'court' => 'High Court',
                'decided_date' => '2021-03-03',
                'start_page' => 13,
                'end_page' => 20,
                'cited_acts' => ['Act C s.3'],
            ],
        ];

        $callCounts = ['detection' => 0, 'batch_summary' => 0, 'fallback_summary' => 0];

        Http::fake([
            '*' => function ($request) use ($cases, &$callCounts) {
                $url = $request->url();
                if (str_contains($url, 'gemini-flash-latest:generateContent')) {
                    $body = $request->data();
                    $parts = $body['contents'][0]['parts'] ?? [];
                    $texts = [];
                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $texts[] = $part['text'];
                        }
                    }
                    $joined = implode("\n", $texts);
                    if (str_contains($joined, 'is_compilation')) {
                        $callCounts['detection']++;

                        return Http::response([
                            'candidates' => [[
                                'content' => ['parts' => [[
                                    'text' => json_encode([
                                        'is_compilation' => true,
                                        'cases' => $cases,
                                    ]),
                                ]]],
                            ]],
                        ], 200);
                    }

                    // Batched multi-case summarisation — all cases in ONE call.
                    if (preg_match_all('/CASE KEY: (\S+)/', $joined, $matches)) {
                        $callCounts['batch_summary']++;

                        $results = [];
                        foreach ($matches[1] as $key) {
                            $results[] = [
                                'key' => $key,
                                'citation' => null,
                                'court' => null,
                                'decided_date' => null,
                                'summary' => 'Slice summary.',
                                'cited_acts' => [],
                            ];
                        }

                        return Http::response([
                            'candidates' => [[
                                'content' => ['parts' => [[
                                    'text' => json_encode(['results' => $results]),
                                ]]],
                            ]],
                        ], 200);
                    }

                    // Per-item fallback (should not be needed when batching succeeds).
                    $callCounts['fallback_summary']++;

                    return Http::response([
                        'candidates' => [[
                            'content' => ['parts' => [[
                                'text' => json_encode([
                                    'citation' => null,
                                    'court' => null,
                                    'decided_date' => null,
                                    'summary' => 'Slice summary.',
                                    'cited_acts' => [],
                                ]),
                            ]]],
                        ]],
                    ], 200);
                }

                if (str_contains($url, 'gemini-embedding-2:embedContent')) {
                    return Http::response([
                        'embedding' => ['values' => [0.1, 0.2, 0.3]],
                    ], 200);
                }

                return Http::response(['error' => 'unexpected '.$url], 500);
            },
        ]);

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')->andReturn('extracted judgment text for embedding');
        });

        $sliceCounter = 0;
        $this->mock(PdfPageSlicer::class, function ($mock) use (&$sliceCounter) {
            $mock->shouldReceive('slice')->andReturnUsing(function () use (&$sliceCounter) {
                $sliceCounter++;

                return "%PDF-1.4\nslice-{$sliceCounter}\n%%EOF";
            });
        });

        $pdf = UploadedFile::fake()->createWithContent(
            'law-report-volume.pdf',
            '%PDF-1.4 unique-source-bytes-for-hash-aaa %%EOF'
        );

        $upload = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf,
        ]);

        $batch = JudgmentImportBatch::query()->first();
        $this->assertNotNull($batch);
        $upload->assertRedirect(route('judgments.imports.review', $batch));
        $this->assertSame(0, Judgment::count());

        $confirmPayload = ['cases' => []];
        foreach ($cases as $i => $case) {
            $confirmPayload['cases'][$i] = [
                'include' => '1',
                'citation' => $case['citation'],
                'court' => $case['court'],
                'decided_date' => $case['decided_date'],
                'start_page' => $case['start_page'],
                'end_page' => $case['end_page'],
                'cited_acts' => implode("\n", $case['cited_acts']),
            ];
        }

        $confirm = $this->actingAs($user)->post(route('judgments.imports.confirm', $batch), $confirmPayload);
        $confirm->assertRedirect(route('judgments.index'));

        // Quota efficiency: 1 detection call + 1 batched summary call for all 3
        // cases (not 3 separate summary calls) is the whole point of batching.
        $this->assertSame(1, $callCounts['detection']);
        $this->assertSame(1, $callCounts['batch_summary'], 'All cases in one compilation should be summarised in a single Gemini call.');
        $this->assertSame(0, $callCounts['fallback_summary'], 'Batching succeeded, so no per-case fallback calls should happen.');

        $this->assertSame(3, Judgment::count());
        $this->assertSame(JudgmentImportBatch::STATUS_COMPLETED, $batch->fresh()->status);

        $paths = Judgment::query()->pluck('pdf_path')->all();
        $this->assertCount(3, array_unique($paths));

        // Re-upload same bytes → attaches to no open batch (completed), creates new batch with items pre-skipped
        $pdf2 = UploadedFile::fake()->createWithContent(
            'law-report-volume.pdf',
            '%PDF-1.4 unique-source-bytes-for-hash-aaa %%EOF'
        );

        $reupload = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf2,
        ]);

        $newBatch = JudgmentImportBatch::query()->latest('id')->first();
        $reupload->assertRedirect(route('judgments.imports.review', $newBatch));
        $this->assertSame(3, $newBatch->items()->where('status', JudgmentImportItem::STATUS_SKIPPED)->count());
        $this->assertSame(3, Judgment::count()); // still no duplicates
    }

    public function test_import_pauses_on_quota_and_resume_skips_finished_cases(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $batch = JudgmentImportBatch::create([
            'source_hash' => hash('sha256', 'quota-test'),
            'source_pdf_path' => 'judgments/imports/quota.pdf',
            'original_filename' => 'quota.pdf',
            'category' => 'judgment',
            'status' => JudgmentImportBatch::STATUS_PAUSED,
            'pause_reason' => 'quota',
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('public')->put($batch->source_pdf_path, '%PDF-1.4 source %%EOF');

        JudgmentImportItem::create([
            'batch_id' => $batch->id,
            'position' => 0,
            'citation' => 'Done Case',
            'start_page' => 1,
            'end_page' => 2,
            'cited_acts' => [],
            'include' => true,
            'status' => JudgmentImportItem::STATUS_COMPLETED,
            'judgment_id' => Judgment::create([
                'title' => 'Done Case',
                'category' => 'judgment',
                'pdf_path' => 'judgments/done.pdf',
                'uploaded_by' => $user->id,
                'source_hash' => $batch->source_hash,
                'source_start_page' => 1,
                'source_end_page' => 2,
                'embedding' => [0.1],
                'summary' => 'done',
            ])->id,
        ]);

        JudgmentImportItem::create([
            'batch_id' => $batch->id,
            'position' => 1,
            'citation' => 'Next Case',
            'start_page' => 3,
            'end_page' => 4,
            'cited_acts' => ['Act X'],
            'include' => true,
            'status' => JudgmentImportItem::STATUS_PENDING,
        ]);

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')->once()->andReturn('next case text');
        });
        $this->mock(PdfPageSlicer::class, function ($mock) {
            $mock->shouldReceive('slice')->once()->andReturn("%PDF-1.4\nnext\n%%EOF");
        });

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent' => Http::response([
                'embedding' => ['values' => [0.2, 0.3]],
            ], 200),
            'generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'citation' => 'Next Case',
                            'court' => null,
                            'decided_date' => null,
                            'summary' => 'Resumed summary.',
                            'cited_acts' => ['Act X'],
                        ]),
                    ]]],
                ]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('judgments.imports.resume', $batch))
            ->assertRedirect(route('judgments.index'));

        $this->assertSame(2, Judgment::count());
        $this->assertSame(JudgmentImportBatch::STATUS_COMPLETED, $batch->fresh()->status);
        $this->assertSame('Resumed summary.', Judgment::where('title', 'Next Case')->value('summary'));
    }

    public function test_ingestion_rotates_to_fallback_api_key_when_primary_key_is_quota_exhausted(): void
    {
        $user = User::factory()->create(['role' => 'clerk', 'status' => 'active']);

        config([
            'services.gemini.key' => 'primary-key',
            'services.gemini.keys' => 'fallback-key',
        ]);
        Cache::forget('gemini_api_key_pool_index');

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')
                ->andReturn(implode(' ', array_fill(0, 50, 'possession')));
        });

        $quotaResponse = Http::response([
            'error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded for today.'],
        ], 429);

        Http::fake([
            '*' => function ($request) use ($quotaResponse) {
                $usedKey = $request->header('x-goog-api-key')[0] ?? null;

                // The primary key is exhausted for every endpoint — this forces
                // GeminiKeyPool to rotate to the fallback key for every call type
                // (detection, summarisation, embedding).
                if ($usedKey === 'primary-key') {
                    return $quotaResponse;
                }

                $url = $request->url();

                if (str_contains($url, 'generateContent')) {
                    $body = $request->data();
                    $texts = array_column($body['contents'][0]['parts'] ?? [], 'text');
                    $joined = implode("\n", $texts);

                    if (str_contains($joined, 'is_compilation')) {
                        return Http::response([
                            'candidates' => [[
                                'content' => ['parts' => [[
                                    'text' => json_encode([
                                        'is_compilation' => false,
                                        'cases' => [[
                                            'citation' => 'Perera v. Silva',
                                            'court' => 'Supreme Court of Sri Lanka',
                                            'decided_date' => '2020-05-12',
                                            'start_page' => 1,
                                            'end_page' => 10,
                                            'cited_acts' => [],
                                        ]],
                                    ]),
                                ]]],
                            ]],
                        ], 200);
                    }

                    return Http::response([
                        'candidates' => [[
                            'content' => ['parts' => [[
                                'text' => json_encode([
                                    'citation' => 'Perera v. Silva',
                                    'court' => 'Supreme Court of Sri Lanka',
                                    'decided_date' => '2020-05-12',
                                    'summary' => 'Indexed using the fallback key.',
                                    'cited_acts' => [],
                                ]),
                            ]]],
                        ]],
                    ], 200);
                }

                if (str_contains($url, 'embedContent')) {
                    return Http::response(['embedding' => ['values' => [0.4, 0.5]]], 200);
                }

                return Http::response(['error' => 'unexpected '.$url], 500);
            },
        ]);

        $pdf = UploadedFile::fake()->create('perera-v-silva.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf,
        ]);

        $response->assertRedirect(route('judgments.index'));

        $judgment = Judgment::query()->first();
        $this->assertNotNull($judgment);
        $this->assertSame('Indexed using the fallback key.', $judgment->summary);
        $this->assertNotEmpty($judgment->embedding);

        // Pool should have rotated forward to (and stayed on) the fallback key.
        $this->assertSame(1, Cache::get('gemini_api_key_pool_index'));
    }

    public function test_import_waits_out_a_short_rate_limit_instead_of_pausing(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $batch = JudgmentImportBatch::create([
            'source_hash' => hash('sha256', 'rate-limit-test'),
            'source_pdf_path' => 'judgments/imports/rate-limit.pdf',
            'original_filename' => 'rate-limit.pdf',
            'category' => 'judgment',
            'status' => JudgmentImportBatch::STATUS_PAUSED,
            'pause_reason' => null,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('public')->put($batch->source_pdf_path, '%PDF-1.4 source %%EOF');

        JudgmentImportItem::create([
            'batch_id' => $batch->id,
            'position' => 0,
            'citation' => 'Rate Limited Case',
            'start_page' => 1,
            'end_page' => 2,
            'cited_acts' => [],
            'include' => true,
            'status' => JudgmentImportItem::STATUS_PENDING,
        ]);

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')->andReturn('rate limited case text');
        });
        $this->mock(PdfPageSlicer::class, function ($mock) {
            $mock->shouldReceive('slice')->andReturn("%PDF-1.4\nrate-limited\n%%EOF");
        });

        Http::fake([
            // First embed attempt hits a short (1s) per-minute rate limit; the
            // second attempt (after the auto-retry sleeps briefly) succeeds.
            'generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent' => Http::sequence()
                ->push([
                    'error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED', 'message' => 'Please retry in 1s.'],
                ], 429)
                ->push(['embedding' => ['values' => [0.2, 0.3]]], 200),
            'generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['results' => [[
                            'key' => (string) JudgmentImportItem::query()->value('id'),
                            'citation' => 'Rate Limited Case',
                            'court' => null,
                            'decided_date' => null,
                            'summary' => 'Indexed after waiting out a rate limit.',
                            'cited_acts' => [],
                        ]]]),
                    ]]],
                ]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('judgments.imports.resume', $batch))
            ->assertRedirect(route('judgments.index'));

        $this->assertSame(1, Judgment::count());
        $this->assertSame(JudgmentImportBatch::STATUS_COMPLETED, $batch->fresh()->status);
        $this->assertSame(
            'Indexed after waiting out a rate limit.',
            Judgment::where('title', 'Rate Limited Case')->value('summary')
        );
    }

    public function test_daily_quota_error_is_not_waited_out_and_marks_key_exhausted(): void
    {
        // Regression test: Gemini's RetryInfo.retryDelay can be a short,
        // misleading hint even for a genuine daily-cap error. Only ONE key is
        // configured here, so if GeminiKeyPool incorrectly treated this as a
        // waitable per-minute limit it would sleep and retry (and this test
        // would take real wall-clock seconds); since it's a real PerDay
        // quotaId it must fail immediately without sleeping.
        $user = User::factory()->create(['role' => 'clerk', 'status' => 'active']);

        config(['services.gemini.key' => 'only-key']);
        Cache::forget('gemini_api_key_pool_index');
        Cache::forget('gemini_api_key_pool_exhausted_0');

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')
                ->andReturn(implode(' ', array_fill(0, 50, 'possession')));
        });

        $dailyQuotaBody = json_encode([
            'error' => [
                'code' => 429,
                'status' => 'RESOURCE_EXHAUSTED',
                'message' => 'You exceeded your current quota.',
                'details' => [
                    [
                        '@type' => 'type.googleapis.com/google.rpc.QuotaFailure',
                        'violations' => [[
                            'quotaId' => 'GenerateRequestsPerDayPerProjectPerModel-FreeTier',
                        ]],
                    ],
                    [
                        '@type' => 'type.googleapis.com/google.rpc.RetryInfo',
                        'retryDelay' => '1s',
                    ],
                ],
            ],
        ]);

        Http::fake([
            '*' => Http::response($dailyQuotaBody, 429),
        ]);

        $start = microtime(true);

        $pdf = UploadedFile::fake()->create('perera-v-silva.pdf', 100, 'application/pdf');
        $response = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf,
        ]);

        $elapsed = microtime(true) - $start;

        $response->assertRedirect(route('judgments.index'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Judgment::count());
        // No 1s+ sleep should have happened since this was correctly
        // classified as non-retryable from the quotaId alone.
        $this->assertLessThan(1.0, $elapsed);
        $this->assertTrue(Cache::get('gemini_api_key_pool_exhausted_0'));

        // A second attempt should skip the HTTP call entirely (key already
        // known exhausted for today) and fail fast with an aggregated message.
        Http::fake([
            '*' => function () {
                $this->fail('Should not dial an already-known-exhausted key again.');
            },
        ]);

        $pdf2 = UploadedFile::fake()->create('another.pdf', 100, 'application/pdf');
        $second = $this->actingAs($user)->post(route('judgments.store'), [
            'category' => 'judgment',
            'pdf' => $pdf2,
        ]);

        $second->assertRedirect(route('judgments.index'));
        $second->assertSessionHas('error');
        $this->assertSame(0, Judgment::count());
    }

    public function test_download_sanitizes_slashes_in_case_title_for_content_disposition(): void
    {
        // Sri Lankan citations routinely contain "/", e.g. "S.C. Appeal No. 123/2010",
        // which Symfony's Content-Disposition header builder rejects outright.
        $user = User::factory()->create(['role' => 'clerk', 'status' => 'active']);

        Storage::disk('public')->put('judgments/slashy.pdf', '%PDF-1.4 content %%EOF');

        $judgment = Judgment::create([
            'title' => 'S.C. Appeal No. 123/2010',
            'category' => 'judgment',
            'pdf_path' => 'judgments/slashy.pdf',
            'uploaded_by' => $user->id,
            'source_hash' => hash('sha256', 'slashy'),
            'embedding' => [0.1],
        ]);

        $response = $this->actingAs($user)->get(route('judgments.download', $judgment));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringNotContainsString('/', $disposition);
        $this->assertStringContainsString('S.C. Appeal No. 123-2010.pdf', $disposition);
    }

    public function test_import_pauses_on_gemini_connection_failure_instead_of_failing_every_case(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $batch = JudgmentImportBatch::create([
            'source_hash' => hash('sha256', 'dns-fail-test'),
            'source_pdf_path' => 'judgments/imports/dns.pdf',
            'original_filename' => 'dns.pdf',
            'category' => 'judgment',
            'status' => JudgmentImportBatch::STATUS_PAUSED,
            'pause_reason' => null,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('public')->put($batch->source_pdf_path, '%PDF-1.4 source %%EOF');

        JudgmentImportItem::create([
            'batch_id' => $batch->id,
            'position' => 0,
            'citation' => 'Case One',
            'start_page' => 1,
            'end_page' => 2,
            'cited_acts' => [],
            'include' => true,
            'status' => JudgmentImportItem::STATUS_PENDING,
        ]);

        JudgmentImportItem::create([
            'batch_id' => $batch->id,
            'position' => 1,
            'citation' => 'Case Two',
            'start_page' => 3,
            'end_page' => 4,
            'cited_acts' => [],
            'include' => true,
            'status' => JudgmentImportItem::STATUS_PENDING,
        ]);

        $this->mock(PdfTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extractFromPath')->andReturn('case text');
        });
        $this->mock(PdfPageSlicer::class, function ($mock) {
            $mock->shouldReceive('slice')->andReturn("%PDF-1.4\nslice\n%%EOF");
        });

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException(
                'cURL error 6: Could not resolve host: generativelanguage.googleapis.com'
            );
        });

        $this->actingAs($user)
            ->post(route('judgments.imports.resume', $batch))
            ->assertRedirect(route('judgments.index'));

        $batch->refresh();
        $this->assertSame(JudgmentImportBatch::STATUS_PAUSED, $batch->status);
        $this->assertStringContainsString('Cannot reach Gemini', (string) $batch->pause_reason);
        $this->assertSame(0, Judgment::count());
        $this->assertSame(0, $batch->items()->where('status', JudgmentImportItem::STATUS_FAILED)->count());
        $this->assertSame(2, $batch->items()->where('status', JudgmentImportItem::STATUS_PENDING)->count());
    }

    public function test_judgment_library_is_open_to_any_authenticated_role(): void
    {
        $clerk = User::factory()->create([
            'role' => 'clerk',
            'status' => 'active',
        ]);

        $this->actingAs($clerk)
            ->get(route('judgments.index'))
            ->assertOk();
    }
}
