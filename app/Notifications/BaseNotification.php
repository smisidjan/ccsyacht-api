<?php

namespace App\Notifications;

use App\Jobs\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable, TenantAware;

    /**
     * Prepare the notification for queueing.
     */
    public function __construct()
    {
        // Set tenant ID when creating the notification
        if (tenancy()->initialized) {
            $this->tenantId = tenant()->id;
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\InitializeTenancy()];
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    /**
     * Create a new mail message with default styling and message stream.
     *
     * @param string $messageStream Postmark message stream (e.g., 'outbound', 'authentication')
     */
    protected function buildMailMessage(string $messageStream = 'outbound'): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->withSymfonyMessage(function ($message) use ($messageStream) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', $messageStream);
            });
    }

    /**
     * Get the frontend URL for building links.
     */
    protected function getFrontendUrl(): string
    {
        return config('app.frontend_url', config('app.url'));
    }

    /**
     * Build a frontend URL with the given path.
     */
    protected function buildFrontendUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->getFrontendUrl(), '/') . '/' . ltrim($path, '/');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}
