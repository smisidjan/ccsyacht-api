<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class MailService
{
    /**
     * Send a generic email.
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $actionText = null,
        ?string $actionUrl = null,
        array $options = []
    ): void {
        Mail::send([], [], function (Message $message) use ($to, $subject, $body, $actionText, $actionUrl, $options) {
            $message->to($to)
                ->subject($subject);

            if (isset($options['from'])) {
                $message->from($options['from']['address'], $options['from']['name'] ?? null);
            }

            if (isset($options['replyTo'])) {
                $message->replyTo($options['replyTo']);
            }

            $html = $this->buildHtmlBody($subject, $body, $actionText, $actionUrl);
            $message->html($html);
        });
    }

    /**
     * Send email using a notification class.
     */
    public function sendNotification(object $notifiable, $notification): void
    {
        $notifiable->notify($notification);
    }

    /**
     * Send email to an email address (not a notifiable model).
     */
    public function sendToEmail(string $email, $notification): void
    {
        \Illuminate\Support\Facades\Notification::route('mail', $email)
            ->notify($notification);
    }

    /**
     * Build HTML email body.
     */
    protected function buildHtmlBody(
        string $subject,
        string $body,
        ?string $actionText,
        ?string $actionUrl
    ): string {
        $appName = config('app.name');
        $primaryColor = '#3490dc';

        $actionButton = '';
        if ($actionText && $actionUrl) {
            $actionButton = <<<HTML
            <tr>
                <td style="padding: 20px 0;">
                    <a href="{$actionUrl}" style="background-color: {$primaryColor}; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                        {$actionText}
                    </a>
                </td>
            </tr>
            HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$subject}</title>
        </head>
        <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: {$primaryColor}; padding: 30px; text-align: center;">
                                    <h1 style="color: white; margin: 0; font-size: 24px;">{$appName}</h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="color: #333; font-size: 16px; line-height: 1.6;">
                                                {$body}
                                            </td>
                                        </tr>
                                        {$actionButton}
                                    </table>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8f8f8; padding: 20px 30px; text-align: center; border-top: 1px solid #eee;">
                                    <p style="color: #888; font-size: 12px; margin: 0;">
                                        &copy; {$appName}. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }

    /**
     * Get the frontend URL.
     */
    public function getFrontendUrl(): string
    {
        return config('app.frontend_url', config('app.url'));
    }

    /**
     * Build a frontend URL with the given path and query parameters.
     */
    public function buildUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->getFrontendUrl(), '/') . '/' . ltrim($path, '/');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}
