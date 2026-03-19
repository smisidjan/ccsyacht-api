<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;

class InvitationSentNotification extends BaseNotification
{
    public function __construct(
        private Invitation $invitation
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = $this->buildFrontendUrl('/invitation/accept', [
            'token' => $this->invitation->token,
            'email' => $this->invitation->email,
        ]);
        $declineUrl = $this->buildFrontendUrl('/invitation/decline', [
            'token' => $this->invitation->token,
        ]);

        return $this->buildMailMessage()
            ->subject('You have been invited to ' . config('app.name'))
            ->markdown('emails.invitation', [
                'invitedByEmail' => $this->invitation->invitedBy->email,
                'role' => $this->invitation->role,
                'expiresAt' => $this->invitation->expires_at->format('F j, Y \a\t H:i'),
                'acceptUrl' => $acceptUrl,
                'declineUrl' => $declineUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'invited_by' => $this->invitation->invitedBy->email,
            'role' => $this->invitation->role,
        ];
    }
}
