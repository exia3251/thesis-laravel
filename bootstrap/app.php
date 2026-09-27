<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Behind a tunnel, believe the tunnel about the scheme.
         *
         * The system is demonstrated over an ngrok URL, which terminates
         * HTTPS at their end and forwards plain HTTP here. Without this,
         * Laravel builds every link as http:// while the page is served over
         * https://, and the browser blocks its own stylesheet as mixed
         * content -- an unstyled site for everybody who follows the link.
         *
         * Safe here because nothing else can reach this server: it listens on
         * localhost and the tunnel agent runs on the same machine.
         */
        $middleware->trustProxies(at: '*');

        /*
         * Somebody already signed in who opens a sign-in page again belongs
         * on their own home screen, not the framework's default of "/".
         *
         * An administrator who clicked through to the staff login while still
         * signed in was being dropped on the shop front, which reads as the
         * back office refusing them. Each guard has a home: staff have the
         * dashboard, customers have the shop.
         */
        $middleware->redirectUsersTo(function () {
            foreach (['staff', 'web'] as $guard) {
                if ($user = auth()->guard($guard)->user()) {
                    return $user->homePath();
                }
            }

            return '/shop';
        });

        // Register middleware aliases
        $middleware->alias([
            // Broad access gates
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'customer' => \App\Http\Middleware\CustomerMiddleware::class,
            'active_session' => \App\Http\Middleware\EnforceSingleSessionMiddleware::class,
            
            // Per-route permission checks
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
