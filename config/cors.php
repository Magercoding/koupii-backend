<?php

return [
    'paths' => [
        'api/*',
        'storage/*',
        'sanctum/csrf-cookie',
    ],
    'allowed_methods' => ['*'],
    'allowed_origins' => env('APP_ENV') === 'production' ? [
        'https://koupii.magercoding.com', // Production frontend
        'https://www.koupii.magercoding.com', // www subdomain
        'http://localhost:3000', // Allow local for testing
    ] : [
        'http://localhost:3000', // Local development frontend
        'http://127.0.0.1:3000', // Alternative local
        'https://koupii.magercoding.com', // Production frontend for testing
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.magercoding\.com$/', // Allow all magercoding.com subdomains
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'supports_credentials' => true, // (!Sanctum)
];
