<?php

namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RegistrationRequestSubmittedNotification extends BaseNotification
{
    public function __construct(
        private RegistrationRequest $registrationRequest
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $reviewUrl = $this->buildFrontendUrl('/admin/registration-requests/' . $this->registrationRequest->id);

        $mail = $this->buildMailMessage()
            ->subject('New Registration Request - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new user has requested to join ' . config('app.name') . '.')
            ->line('**Name:** ' . $this->registrationRequest->name)
            ->line('**Email:** ' . $this->registrationRequest->email);

        if ($this->registrationRequest->message) {
            $mail->line('**Message:** ' . $this->registrationRequest->message);
        }

        return $mail->action('Review Request', $reviewUrl)
            ->line('Please review this request and approve or reject it.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'registration_request_id' => $this->registrationRequest->id,
            'name' => $this->registrationRequest->name,
            'email' => $this->registrationRequest->email,
        ];
    }
}
