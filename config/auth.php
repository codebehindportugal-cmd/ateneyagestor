<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    // Two panels, two guards: 'web' for staff (App\Models\User) using the
    // Filament Admin panel, 'client' for customers (App\Models\Client)
    // using the Filament Client (portal) panel. The Pi agent does not use
    // either of these -- it authenticates via a Sanctum personal access
    // token tied to App\Models\Agent (see routes/api.php).
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'client' => [
            'driver' => 'session',
            'provider' => 'clients',
        ],
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => null,
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'clients' => [
            'driver' => 'eloquent',
            'model' => App\Models\Client::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'clients' => [
            'provider' => 'clients',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
