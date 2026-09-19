<?php

namespace Tests\Feature;

use App\Models\CaseCategory;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_case_category_seeder_builds_the_expected_three_level_hierarchy(): void
    {
        $this->assertGreaterThanOrEqual(3, CaseCategory::level(CaseCategory::LEVEL_MAIN_TYPE)->count());
        $this->assertGreaterThan(0, CaseCategory::level(CaseCategory::LEVEL_GROUP)->count());
        $this->assertGreaterThan(0, CaseCategory::level(CaseCategory::LEVEL_SPECIFIC_TYPE)->count());

        $civil = CaseCategory::where('name', 'Civil')->where('level', CaseCategory::LEVEL_MAIN_TYPE)->firstOrFail();
        $propertyGroup = CaseCategory::where('name', 'Property & Land Law')->where('parent_id', $civil->id)->firstOrFail();
        $partitionLeaf = CaseCategory::where('name', 'Partition action')->where('parent_id', $propertyGroup->id)->first();

        $this->assertNotNull($partitionLeaf);
        $this->assertSame('Civil › Property & Land Law › Partition action', $partitionLeaf->fullPath());

        // Every group has a trailing "Other" leaf.
        $this->assertTrue($propertyGroup->children()->where('name', 'Other')->exists());
    }

    public function test_creating_a_case_requires_a_leaf_level_category(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $client = \App\Models\Client::first();
        $attorney = User::where('role', '!=', 'clerk')->where('status', 'active')->first();

        $group = CaseCategory::level(CaseCategory::LEVEL_GROUP)->firstOrFail();

        // Selecting a group (not a leaf) must fail validation.
        $response = $this->actingAs($partner)->post(route('cases.store'), [
            'client_id' => $client->id,
            'assigned_attorney_id' => $attorney->id,
            'case_category_id' => $group->id,
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('case_category_id');

        // Selecting an actual leaf succeeds.
        $leaf = $group->children()->firstOrFail();

        $response = $this->actingAs($partner)->post(route('cases.store'), [
            'client_id' => $client->id,
            'assigned_attorney_id' => $attorney->id,
            'case_category_id' => $leaf->id,
            'court_id' => Court::first()->id,
            'applicable_law' => LegalCase::APPLICABLE_LAW_GENERAL,
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('cases.index'));
        $this->assertDatabaseHas('legal_cases', [
            'client_id' => $client->id,
            'case_category_id' => $leaf->id,
            'court_id' => Court::first()->id,
        ]);
    }

    public function test_an_inactive_category_cannot_be_selected_for_a_new_case(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $client = \App\Models\Client::first();
        $attorney = User::where('role', '!=', 'clerk')->where('status', 'active')->first();

        $leaf = CaseCategory::level(CaseCategory::LEVEL_SPECIFIC_TYPE)->firstOrFail();
        $leaf->update(['is_active' => false]);

        $response = $this->actingAs($partner)->post(route('cases.store'), [
            'client_id' => $client->id,
            'assigned_attorney_id' => $attorney->id,
            'case_category_id' => $leaf->id,
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('case_category_id');
    }

    public function test_only_partner_can_manage_case_category_taxonomy(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $associate = User::where('email', 'associate@lexlanka.lk')->firstOrFail();

        $this->actingAs($partner)->get(route('case-categories.index'))->assertOk();
        $this->actingAs($associate)->get(route('case-categories.index'))->assertForbidden();

        $main = CaseCategory::level(CaseCategory::LEVEL_MAIN_TYPE)->firstOrFail();

        $this->actingAs($associate)->post(route('case-categories.store'), [
            'name' => 'Should Not Be Created',
            'level' => CaseCategory::LEVEL_GROUP,
            'parent_id' => $main->id,
        ])->assertForbidden();

        $this->actingAs($partner)->post(route('case-categories.store'), [
            'name' => 'A New Group',
            'level' => CaseCategory::LEVEL_GROUP,
            'parent_id' => $main->id,
        ])->assertRedirect(route('case-categories.index'));

        $this->assertDatabaseHas('case_categories', [
            'name' => 'A New Group',
            'parent_id' => $main->id,
            'level' => CaseCategory::LEVEL_GROUP,
        ]);
    }

    public function test_deleting_a_category_in_use_is_blocked_but_deactivating_works(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $inUseCategory = LegalCase::first()->caseCategory;

        $this->actingAs($partner)
            ->delete(route('case-categories.destroy', $inUseCategory))
            ->assertRedirect(route('case-categories.index'));

        $this->assertDatabaseHas('case_categories', ['id' => $inUseCategory->id]);

        $this->actingAs($partner)
            ->patch(route('case-categories.toggle-active', $inUseCategory))
            ->assertRedirect(route('case-categories.index'));

        $this->assertDatabaseHas('case_categories', ['id' => $inUseCategory->id, 'is_active' => false]);
    }

    public function test_partner_can_edit_and_update_a_category(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $group = CaseCategory::level(CaseCategory::LEVEL_GROUP)->firstOrFail();

        $this->actingAs($partner)->get(route('case-categories.edit', $group))->assertOk();

        $this->actingAs($partner)->put(route('case-categories.update', $group), [
            'name' => 'Renamed Group',
            'level' => CaseCategory::LEVEL_GROUP,
            'parent_id' => $group->parent_id,
        ])->assertRedirect(route('case-categories.index'));

        $this->assertDatabaseHas('case_categories', ['id' => $group->id, 'name' => 'Renamed Group']);
    }

    public function test_case_create_edit_and_index_pages_render_with_the_cascading_category_picker(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $case = LegalCase::first();

        $this->actingAs($partner)->get(route('cases.create'))
            ->assertOk()
            ->assertSee('Main Type')
            ->assertSee('Specific Type')
            ->assertSee('Court / Forum')
            ->assertSee('Applicable Law');

        $this->actingAs($partner)->get(route('cases.edit', $case))
            ->assertOk()
            ->assertSee('Main Type')
            ->assertSee('Specific Type');

        $this->actingAs($partner)->get(route('cases.index'))
            ->assertOk()
            ->assertSee('Case Type');
    }

    public function test_only_partner_can_manage_courts(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $clerk = User::where('email', 'clerk@lexlanka.lk')->firstOrFail();

        $this->actingAs($partner)->get(route('courts.index'))->assertOk();
        $this->actingAs($clerk)->get(route('courts.index'))->assertForbidden();

        $this->actingAs($partner)->post(route('courts.store'), [
            'name' => 'Test Tribunal',
            'tier' => Court::TIER_ADR,
        ])->assertRedirect(route('courts.index'));

        $this->assertDatabaseHas('courts', ['name' => 'Test Tribunal', 'tier' => Court::TIER_ADR]);
    }

    public function test_taxonomy_admin_pages_render(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $group = CaseCategory::level(CaseCategory::LEVEL_GROUP)->firstOrFail();
        $court = Court::first();

        $this->actingAs($partner)->get(route('case-categories.index'))->assertOk();
        $this->actingAs($partner)->get(route('case-categories.create'))->assertOk();
        $this->actingAs($partner)->get(route('case-categories.edit', $group))->assertOk();

        $this->actingAs($partner)->get(route('courts.index'))->assertOk();
        $this->actingAs($partner)->get(route('courts.create'))->assertOk();
        $this->actingAs($partner)->get(route('courts.edit', $court))->assertOk();
    }
}
