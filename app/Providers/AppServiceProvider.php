<?php

namespace App\Providers;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\Sale;
use App\Observers\SaleObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

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
        Sale::observe(SaleObserver::class);

        /*
         * Never show a stack trace to somebody who arrived over a tunnel.
         *
         * This runs with APP_DEBUG=true, which is right on a laptop and
         * dangerous the moment the port is shared: Laravel's error page lists
         * the whole environment, and the environment holds the database
         * password and the assistant's API key. The survey link is handed to
         * strangers, and one unhandled exception would hand them those.
         *
         * Local requests keep the detailed page, because that is the one that
         * makes it worth having.
         */
        if (! self::isLocalRequest()) {
            config(['app.debug' => false]);
        }

        // Laravel's stock verification mail is unbranded markdown. Point it at
        // the same layout the order emails use so a customer sees one sender.
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirm your email address - RANEY LUBRICANTS')
                ->view('emails.verify-email', [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });

        /*
         * The reset link points at the shop's own page rather than the route
         * name Laravel assumes, and the mail uses the same layout as every
         * other message this business sends.
         *
         * The address travels with the token because the token alone does not
         * say whose it is: the table is keyed by email, so the form has to
         * hand both back for the pair to be checked.
         */
        ResetPassword::createUrlUsing(
            fn ($notifiable, string $token) => PasswordResetController::linkFor($notifiable, $token)
        );

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            return (new MailMessage)
                ->subject('Reset your password - RANEY LUBRICANTS')
                ->view('emails.reset-password', [
                    'user' => $notifiable,
                    'url' => PasswordResetController::linkFor($notifiable, $token),
                    'minutes' => config('auth.passwords.users.expire'),
                ]);
        });
    }

    /**
     * Whether this request came from the machine the server runs on.
     *
     * By host name rather than by IP, because a tunnel agent runs on this
     * same machine and every request it forwards arrives from 127.0.0.1. What
     * separates them is what the visitor typed: localhost, or a public
     * address belonging to the tunnel.
     */
    public static function isLocalRequest(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return in_array(
            request()->getHost(),
            ['localhost', '127.0.0.1', '::1', '[::1]'],
            true
        );
    }
}
