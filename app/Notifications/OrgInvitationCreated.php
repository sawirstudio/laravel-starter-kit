<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OrgInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrgInvitationCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(OrgInvitation $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(OrgInvitation $notifiable): MailMessage
    {
        $organization = $notifiable->organization()->firstOrFail();
        $inviter = $notifiable->createdBy()->firstOrFail();

        return (new MailMessage)
            ->subject("You're invited to join {$organization->name}")
            ->line("{$inviter->name} has invited you to join {$organization->name}.")
            ->action('Accept invitation', $notifiable->acceptUrl())
            ->line("This invitation expires on {$notifiable->expires_at->toDayDateTimeString()}.")
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
