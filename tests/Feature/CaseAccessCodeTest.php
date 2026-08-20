<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAccessCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_clerk_is_redirected_to_access_code_form_for_coded_case(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();

        $response = $this->actingAs($clerk)->get("/cases/{$codedCase->id}");

        $response->assertRedirect(route('cases.access-code.show', $codedCase));
    }

    public function test_clerk_without_access_code_configured_is_forbidden(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $uncodedCase = LegalCase::whereNull('access_code_hash')->firstOrFail();

        $response = $this->actingAs($clerk)->get("/cases/{$uncodedCase->id}");

        $response->assertForbidden();
    }

    public function test_access_code_form_not_shown_for_uncoded_case(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $uncodedCase = LegalCase::whereNull('access_code_hash')->firstOrFail();

        $response = $this->actingAs($clerk)->get(route('cases.access-code.show', $uncodedCase));

        $response->assertRedirect(route('cases.show', $uncodedCase));
    }

    public function test_wrong_access_code_is_rejected_and_does_not_grant_session_access(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();

        $response = $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
            'access_code' => 'wrong-code',
        ]);

        $response->assertSessionHasErrors('access_code');

        $followUp = $this->actingAs($clerk)->get("/cases/{$codedCase->id}");
        $followUp->assertRedirect(route('cases.access-code.show', $codedCase));
    }

    public function test_correct_access_code_grants_session_access_to_case_and_documents(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();

        $verify = $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
            'access_code' => '247100',
        ]);

        $verify->assertRedirect(route('cases.show', $codedCase));

        // Same session, no re-entry needed.
        $showResponse = $this->actingAs($clerk)->get("/cases/{$codedCase->id}");
        $showResponse->assertOk();

        $document = $codedCase->documents()->first();
        if ($document) {
            $docResponse = $this->actingAs($clerk)->get(route('documents.show', $document));
            $docResponse->assertOk();
        }
    }

    public function test_clerk_document_access_redirects_when_case_unverified(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();
        $document = $codedCase->documents()->first();

        if (! $document) {
            $this->markTestSkipped('No document found for the coded case in seed data.');
        }

        $response = $this->actingAs($clerk)->get(route('documents.show', $document));
        $response->assertRedirect(route('cases.access-code.show', $codedCase));

        $downloadResponse = $this->actingAs($clerk)->get(route('documents.download', $document));
        $downloadResponse->assertRedirect(route('cases.access-code.show', $codedCase));

        $previewResponse = $this->actingAs($clerk)->get(route('documents.preview', $document));
        $previewResponse->assertRedirect(route('cases.access-code.show', $codedCase));
    }

    public function test_access_code_verification_redirects_back_to_originally_requested_document(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();
        $document = $codedCase->documents()->first();

        if (! $document) {
            $this->markTestSkipped('No document found for the coded case in seed data.');
        }

        // Hitting the document first records the "intended" URL.
        $this->actingAs($clerk)->get(route('documents.show', $document));

        $verify = $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
            'access_code' => '247100',
        ]);

        $verify->assertRedirect(route('documents.show', $document));
    }

    public function test_only_partner_or_owning_associate_can_manage_access_code(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $owningAssociate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();
        $nonOwningAssociate = User::where('email', 'associate2@lexlanka.lk')->firstOrFail();
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();

        $ownedByAssociate = LegalCase::where('assigned_attorney_id', $owningAssociate->id)->firstOrFail();
        $ownedByPartner = LegalCase::where('assigned_attorney_id', $partner->id)->firstOrFail();

        // Partner can manage any case's code.
        $this->actingAs($partner)
            ->patch(route('cases.access-code.update', $ownedByAssociate), ['access_code' => 'new-code-1'])
            ->assertRedirect(route('cases.edit', $ownedByAssociate));

        // Owning associate can manage their own case's code.
        $this->actingAs($owningAssociate)
            ->patch(route('cases.access-code.update', $ownedByAssociate), ['access_code' => 'new-code-2'])
            ->assertRedirect(route('cases.edit', $ownedByAssociate));

        // Non-owning associate cannot.
        $this->actingAs($nonOwningAssociate)
            ->patch(route('cases.access-code.update', $ownedByAssociate), ['access_code' => 'new-code-3'])
            ->assertForbidden();

        // Clerk can never manage a code, even for a case they've verified.
        $this->actingAs($clerk)
            ->withSession(["case_access_verified.{$ownedByPartner->id}" => true])
            ->patch(route('cases.access-code.update', $ownedByPartner), ['access_code' => 'new-code-4'])
            ->assertForbidden();
    }

    public function test_verified_clerk_still_cannot_edit_or_update_case(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();

        $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
            'access_code' => '247100',
        ]);

        $this->actingAs($clerk)->get(route('cases.edit', $codedCase))->assertForbidden();

        $this->actingAs($clerk)->put(route('cases.update', $codedCase), [
            'client_id' => $codedCase->client_id,
            'assigned_attorney_id' => $codedCase->assigned_attorney_id,
            'status' => $codedCase->status,
        ])->assertForbidden();
    }

    public function test_access_code_store_route_is_rate_limited(): void
    {
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();
        $codedCase = LegalCase::where('access_code_hash', '!=', null)->firstOrFail();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
                'access_code' => 'wrong-code',
            ]);
        }

        $response = $this->actingAs($clerk)->post(route('cases.access-code.store', $codedCase), [
            'access_code' => 'wrong-code',
        ]);

        $response->assertStatus(429);
    }
}
