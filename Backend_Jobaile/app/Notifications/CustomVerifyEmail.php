<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    /**
     * Get the verification email notification.
     */
    public function toMail($notifiable)
    {
        // Buat signed verification URL
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email Anda')
            ->line('Silakan verifikasi email Anda untuk mengaktifkan akun.')
            ->action('Verifikasi Sekarang', $verificationUrl)
            ->line('Jika Anda tidak mendaftar akun, abaikan email ini.');
    }

    protected function verificationUrl($notifiable)
    {
        $temporarySignedRoute = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->id_user,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return $temporarySignedRoute;
    }

}
