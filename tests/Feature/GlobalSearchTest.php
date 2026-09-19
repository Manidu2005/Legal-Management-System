<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_search_finds_document_by_custom_name(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $case = LegalCase::firstOrFail();

        Document::create([
            'case_id' => $case->id,
            'name' => 'UniqueSearchableTitle',
            'file_path' => 'documents/random-hash.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $partner->id,
        ]);

        $response = $this->actingAs($partner)->get('/search?q=UniqueSearchableTitle');

        $response->assertOk();
        $documents = $response->viewData('documents');
        $this->assertCount(1, $documents);
        $this->assertEquals('UniqueSearchableTitle', $documents->first()->name);
    }

    public function test_search_link_is_present_in_navigation(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($partner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('search.index'), false);
    }
}
