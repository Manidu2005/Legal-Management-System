<?php

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CaseMilestoneNotification extends Notification
{
    use Queueable;

    /**
     * The legal case instance.
     */
    protected LegalCase $legalCase;

    /**
     * The new status of the case.
     */
    protected string $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(LegalCase $legalCase, string $status)
    {
        $this->legalCase = $legalCase;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $id = $this->legalCase->id;
        $statusLabel = str_replace('_', ' ', ucwords($this->status, '_'));

        $english = "LexLanka: Your case #{$id} status has been updated to {$statusLabel}. Contact your attorney for details.";
        $sinhala = "LexLanka: ඔබගේ නඩුව #{$id} තත්ත්වය {$statusLabel} ලෙස යාවත්කාලීන කර ඇත. වැඩි විස්තර සඳහා ඔබේ නීතිඥයා අමතන්න.";

        return "{$english}\n{$sinhala}";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->legalCase->id,
            'status' => $this->status,
            'type' => 'case_milestone',
        ];
    }
}
