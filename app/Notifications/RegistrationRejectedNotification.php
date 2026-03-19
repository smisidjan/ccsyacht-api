<?php

namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RegistrationRejectedNotification extends BaseNotification
{
    public function __construct(
        private RegistrationRequest $registrationRequest
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->buildMailMessage()
            ->subject('Registration Request Update - ' . config('app.name'))
            ->greeting('Hello ' . $this->registrationRequest->name . ',')
            ->line('We have reviewed your registration request for ' . config('app.name') . '.')
            ->line('Unfortunately, your request has not been approved at this time.');

        if ($this->registrationRequest->rejection_reason) {
            $mail->line('**Reason:** ' . $this->registrationRequest->rejection_reason);
        }

        return $mail->line('If you believe this was a mistake or have questions, please contact our support team.')
            ->line('Thank you for your interest in ' . config('app.name') . '.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'registration_request_id' => $this->registrationRequest->id,
            'status' => 'rejected',
            'reason' => $this->registrationRequest->rejection_reason,
        ];
    }
}
