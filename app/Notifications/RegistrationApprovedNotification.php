<?php

namespace App\Notifications;

use App\Models\RegistrationRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RegistrationApprovedNotification extends BaseNotification
{
    public function __construct(
        private RegistrationRequest $registrationRequest
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = $this->buildFrontendUrl('/login');

        return $this->buildMailMessage()
            ->subject('Your Registration Has Been Approved - ' . config('app.name'))
            ->greeting('Welcome to ' . config('app.name') . ', ' . $this->registrationRequest->name . '!')
            ->line('Great news! Your registration request has been approved.')
            ->line('You can now log in to your account using the password you provided during registration.')
            ->action('Log In Now', $loginUrl)
            ->line('Thank you for joining ' . config('app.name') . '!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'registration_request_id' => $this->registrationRequest->id,
            'status' => 'approved',
        ];
    }
}
