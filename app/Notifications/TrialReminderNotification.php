<?php

namespace App\Notifications;

use App\Models\CourtDate;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The court date instance.
     */
    protected CourtDate $courtDate;

    /**
     * Create a new notification instance.
     */
    public function __construct(CourtDate $courtDate)
    {
        $this->courtDate = $courtDate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $case = $this->courtDate->legalCase;
        $clientName = $case->client->name ?? 'N/A';
        $caseId = $case->id;
        $date = $this->courtDate->date->format('l, F j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject("Trial Date Reminder - Case #{$caseId}")
            ->greeting("Dear {$notifiable->name},")
            ->line("This is a reminder that you have an upcoming trial date.")
            ->line("**Case:** #{$caseId}")
            ->line("**Client:** {$clientName}")
            ->line("**Case Type:** {$case->case_type}")
            ->line("**Trial Date:** {$date}")
            ->line('Please ensure all necessary preparations are completed before the trial date.')
            ->action('View Court Dates', url('/court-dates'))
            ->line('Thank you for using LexLanka.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $case = $this->courtDate->legalCase;
        $clientName = $case->client->name ?? 'N/A';
        $date = $this->courtDate->date->format('M j, Y g:i A');

        return "Reminder: Trial date for Case #{$case->id} ({$clientName}) is on {$date}. Please prepare accordingly.";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $case = $this->courtDate->legalCase;

        return [
            'court_date_id' => $this->courtDate->id,
            'case_id' => $case->id,
            'client_name' => $case->client->name ?? 'N/A',
            'case_type' => $case->case_type,
            'trial_date' => $this->courtDate->date->toIso8601String(),
            'type' => 'trial_reminder',
        ];
    }
}
