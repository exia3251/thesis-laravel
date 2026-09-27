<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Sign-In
    |--------------------------------------------------------------------------
    | The redirect must match a URI registered on the OAuth client exactly,
    | character for character, or Google refuses the handover.
    |
    | Which is why there is no default here any more. It used to fall back to
    | APP_URL, and APP_URL said port 8000 while the server ran on 8123, so
    | sign-in was broken and said nothing about it until somebody clicked the
    | button. The same address also has to change every time the site is
    | reached from somewhere else -- localhost while developing, a tunnel
    | while the system is being demonstrated.
    |
    | Left empty, the controller builds it from the address the visitor
    | actually arrived on, which is right in both cases. Set it only to pin
    | the callback to one fixed address.
    */
    'google' => [
        /*
         * A switch of its own, so the feature can be turned off without
         * deleting the credentials that make it work.
         *
         * It is off while the system is demonstrated over a tunnel. Google
         * will not accept an ngrok address as an authorised domain -- those
         * live on the Public Suffix List, where anybody can take a subdomain,
         * so Google has no way to tell that this one is ours -- and an app
         * that cannot be published is limited to a hundred named test users,
         * which a survey of strangers cannot work with.
         *
         * Rather than leave a button that sends people to a Google error
         * page, the button goes. Everything behind it stays, and still works
         * on localhost, where the callback is an address Google accepts.
         */
        'enabled' => filter_var(env('GOOGLE_SIGNIN_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
