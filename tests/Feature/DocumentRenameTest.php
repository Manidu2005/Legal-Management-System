<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRenameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_uploader_can_rename_own_document(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $case = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();

        $document = Document::create([
            'case_id' => $case->id,
            'name' => 'Old Name',
            'file_path' => 'documents/test.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $associate->id,
        ]);

        $response = $this->actingAs($associate)->patch("/documents/{$document->id}/rename", [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertEquals('New Name', $document->fresh()->name);
    }

    public function test_non_uploader_non_partner_cannot_rename_document(): void
    {
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $otherCase = LegalCase::where('assigned_attorney_id', '!=', $associate->id)->firstOrFail();

        $document = Document::create([
            'case_id' => $otherCase->id,
            'name' => 'Old Name',
            'file_path' => 'documents/test2.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $otherCase->assigned_attorney_id,
        ]);

        $response = $this->actingAs($associate)->patch("/documents/{$document->id}/rename", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
        $this->assertEquals('Old Name', $document->fresh()->name);
    }

    public function test_partner_can_rename_any_document(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $case = LegalCase::where('assigned_attorney_id', $associate->id)->firstOrFail();

        $document = Document::create([
            'case_id' => $case->id,
            'name' => 'Old Name',
            'file_path' => 'documents/test3.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $associate->id,
        ]);

        $response = $this->actingAs($partner)->patch("/documents/{$document->id}/rename", [
            'name' => 'Partner Renamed',
        ]);

        $response->assertRedirect(route('documents.show', $document));
        $this->assertEquals('Partner Renamed', $document->fresh()->name);
    }

    public function test_rename_requires_a_name(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $case = LegalCase::firstOrFail();

        $document = Document::create([
            'case_id' => $case->id,
            'name' => 'Old Name',
            'file_path' => 'documents/test4.pdf',
            'file_type' => 'pdf',
            'category' => 'evidence',
            'uploaded_by' => $partner->id,
        ]);

        $response = $this->actingAs($partner)->patch("/documents/{$document->id}/rename", [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
