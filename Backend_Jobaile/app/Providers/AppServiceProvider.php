<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $temporarySignedRoute = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(), // id string (UUID atau lainnya)
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
    
            // Misal ubah domain jadi deep link custom
            $parsedUrl = parse_url($temporarySignedRoute);
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
    
            return 'cipeats://email-verify' . $query;
        });
    }
}
