<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Models\TenantRegistrationToken;
use Illuminate\Notifications\Messages\MailMessage;

class TenantAdminRegistrationNotification extends BaseNotification
{
    public function __construct(
        private TenantRegistrationToken $token,
        private Tenant $tenant
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $registerUrl = $this->buildFrontendUrl("/register/{$this->tenant->slug}", [
            'token' => $this->token->token,
            'email' => $this->token->email,
        ]);

        return $this->buildMailMessage()
            ->subject("You've been invited to manage {$this->tenant->name}")
            ->greeting('Hello!')
            ->line("You have been invited as administrator of **{$this->tenant->name}**.")
            ->line('Click the button below to create your account and get started.')
            ->action('Create Account', $registerUrl)
            ->line('This link will expire in 7 days.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'token_id' => $this->token->id,
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ];
    }
}
