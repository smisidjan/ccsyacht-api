<?php

namespace App\Providers;

use App\Mail\MailtrapTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MailtrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Mail::extend('mailtrap', function (array $config = []) {
            $apiKey = $config['api_key'] ?? env('MAILTRAP_API_KEY');

            if (!$apiKey) {
                throw new \InvalidArgumentException('Mailtrap API key is not configured');
            }

            return new MailtrapTransport($apiKey);
        });
    }
}