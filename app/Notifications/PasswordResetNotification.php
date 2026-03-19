<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends BaseNotification
{
    public function __construct(
        private string $token,
        private string $resetPath = '/reset-password'
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->buildFrontendUrl($this->resetPath, [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return $this->buildMailMessage('authentication')
            ->subject('Reset Your Password')
            ->greeting('Hello!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
