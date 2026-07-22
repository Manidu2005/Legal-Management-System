<?php

namespace App\Console\Commands;

use App\Models\CourtDate;
use App\Notifications\TrialReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lexlanka:send-trial-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 48-hour trial date reminders to assigned attorneys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $cutoff = $now->copy()->addHours(48);

        $courtDates = CourtDate::with(['legalCase.client', 'legalCase.assignedAttorney'])
            ->where('type', 'trial_date')
            ->where('reminder_sent', false)
            ->whereBetween('date', [$now, $cutoff])
            ->get();

        $sentCount = 0;

        foreach ($courtDates as $courtDate) {
            $attorney = $courtDate->legalCase->assignedAttorney;

            if (!$attorney) {
                $this->warn("Court date #{$courtDate->id} has no assigned attorney — skipping.");
                Log::warning("Court date #{$courtDate->id} for case #{$courtDate->case_id} has no assigned attorney.");
                continue;
            }

            $attorney->notify(new TrialReminderNotification($courtDate));

            $courtDate->update(['reminder_sent' => true]);

            $sentCount++;
        }

        $this->info("Sent {$sentCount} trial date reminder(s).");
        Log::info("SendTrialReminders: Sent {$sentCount} reminder(s).");

        return self::SUCCESS;
    }
}
