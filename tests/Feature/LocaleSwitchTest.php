<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_nav_renders_in_english_by_default(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $response = $this->actingAs($partner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Cases');
    }

    public function test_switching_to_sinhala_translates_the_nav(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $this->actingAs($partner)
            ->post(route('locale.update'), ['locale' => 'si'])
            ->assertRedirect();

        $response = $this->actingAs($partner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('පාලනය');
        $response->assertSee('නඩු');
        $response->assertDontSee('Dashboard');
    }

    public function test_switching_to_tamil_translates_the_nav(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $this->actingAs($partner)
            ->post(route('locale.update'), ['locale' => 'ta'])
            ->assertRedirect();

        $response = $this->actingAs($partner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('கட்டுப்பாட்டு பலகை');
        $response->assertSee('வழக்குகள்');
    }

    public function test_locale_choice_persists_to_the_user_account(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $this->assertSame('en', $partner->locale);

        $this->actingAs($partner)->post(route('locale.update'), ['locale' => 'ta']);

        $this->assertSame('ta', $partner->fresh()->locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $this->actingAs($partner)
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');
    }

    public function test_guest_can_switch_locale_via_session_without_a_user_account(): void
    {
        $this->post(route('locale.update'), ['locale' => 'si'])
            ->assertRedirect();

        $this->assertSame('si', session('locale'));
    }
}
