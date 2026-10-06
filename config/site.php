<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Identity
    |--------------------------------------------------------------------------
    |
    | Brand and contact details shown across the public site, the affiliate
    | portal and the admin area. The bracketed values are placeholders from
    | the design and should be replaced with the client's real details.
    |
    */

    'name' => 'Dr. [Full Name], DPT',

    'short_name' => 'Dr. [Name]',

    'initials' => 'Dr',

    'tagline' => 'US Physical Therapy Pathway',

    'description' => 'Honest, personal guidance for Pakistani DPT graduates building a physical therapy career in the United States.',

    'email' => '[email@domain.com]',

    'whatsapp' => '[+92 …]',

    'social_handle' => '[@handle]',

    'socials' => 'Instagram · YouTube · LinkedIn',

    /*
    |--------------------------------------------------------------------------
    | Time & Money
    |--------------------------------------------------------------------------
    |
    | Timestamps are stored in UTC. Dates shown to people, and the "which day"
    | buckets used by reports, use this timezone. Money is whole US dollars.
    |
    */

    'timezone' => 'Asia/Karachi',

    /*
    |--------------------------------------------------------------------------
    | Setting Defaults
    |--------------------------------------------------------------------------
    |
    | Fallbacks for everything an admin can change under Admin → Settings.
    | App\Support\Settings returns these until a value has been saved.
    |
    */

    'settings' => [
        'notifications' => [
            'lead_recipients' => env('ADMIN_EMAIL'),
            'order_recipients' => env('ADMIN_EMAIL'),
        ],
        'contact' => [
            'email' => '[email@domain.com]',
            'whatsapp' => '[+92 …]',
        ],
        'content' => [
            'intro_video_url' => null,
            'lead_video_url' => null,
        ],
        'affiliates' => [
            'default_commission_rate' => 20,
            'attribution_days' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeded Admin
    |--------------------------------------------------------------------------
    |
    | Credentials for the first admin account, created by the database seeder.
    | Leave them empty to skip it and use `php artisan app:create-admin`.
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    |
    | Mail and affiliate click tracking can run on their own queues so a burst
    | of clicks never delays an email. Locally both use the default queue.
    |
    */

    'queues' => [
        'mail' => env('QUEUE_MAIL', 'default'),
        'tracking' => env('QUEUE_TRACKING', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Form Protection
    |--------------------------------------------------------------------------
    |
    | The public forms have no session, so instead of CSRF tokens they rely on
    | rate limits, a honeypot field and a minimum time to fill in the form.
    |
    */

    'spam' => [
        'honeypot' => 'website',
        'min_fill_seconds' => 2,
    ],

    'rate_limits' => [
        'leads' => 5,
        'contact' => 5,
        'checkout' => 10,
        'tracking' => 60,
        'login' => 5,
    ],

];
