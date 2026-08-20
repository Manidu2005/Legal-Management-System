<?php

namespace Tests\Feature;

use App\Models\CourtDate;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtScheduleCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_calendar_hover_summary_lists_appearances_on_that_date(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();
        $case = LegalCase::with('client')->firstOrFail();

        $scheduledAt = now()->copy()->startOfMonth()->addDays(5)->setTime(16, 17);
        CourtDate::create([
            'case_id' => $case->id,
            'date' => $scheduledAt,
            'type' => 'trial_date',
            'reminder_sent' => false,
        ]);

        $dayKey = $scheduledAt->toDateString();

        $response = $this->actingAs($partner)->get(route('court-dates.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('calendarDates')->has($dayKey));
        $response->assertSee('data-calendar-day="'.$dayKey.'"', false);
        $response->assertSee('data-calendar-tooltip-panel', false);
        $response->assertSee('4:17 PM');
        $response->assertSee(__('Trial Date'));
        $response->assertSee($case->display_name);
    }

    public function test_calendar_does_not_show_a_summary_tab_on_empty_days(): void
    {
        $partner = User::where('email', 'partner@lexlanka.lk')->firstOrFail();

        $emptyDay = now()->copy()->startOfMonth()->addDays(1);
        CourtDate::query()
            ->whereDate('date', $emptyDay->toDateString())
            ->delete();

        $response = $this->actingAs($partner)->get(route('court-dates.index'));

        $response->assertOk();
        $this->assertFalse($response->viewData('calendarDates')->has($emptyDay->toDateString()));
        $response->assertDontSee('data-calendar-day="'.$emptyDay->toDateString().'"', false);
    }
}
