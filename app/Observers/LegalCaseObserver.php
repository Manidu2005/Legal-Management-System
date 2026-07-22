<?php

namespace App\Observers;

use App\Models\LegalCase;
use App\Notifications\CaseMilestoneNotification;
use Illuminate\Support\Facades\Log;

class LegalCaseObserver
{
    /**
     * Handle the LegalCase "updated" event.
     */
    public function updated(LegalCase $legalCase): void
    {
        // Only trigger if the status field actually changed
        if (!$legalCase->isDirty('status')) {
            return;
        }

        $newStatus = $legalCase->status;

        // Only trigger for milestone statuses
        if (!in_array($newStatus, LegalCase::MILESTONE_STATUSES)) {
            return;
        }

        // Load the client relationship
        $client = $legalCase->client;

        if (!$client) {
            Log::warning("LegalCaseObserver: Case #{$legalCase->id} has no associated client — skipping milestone notification.");
            return;
        }

        // Send the bilingual milestone notification to the client
        $client->notify(new CaseMilestoneNotification($legalCase, $newStatus));

        Log::info("LegalCaseObserver: Sent milestone notification for case #{$legalCase->id} — status changed to '{$newStatus}', client: {$client->name} ({$client->phone}).");
    }
}
