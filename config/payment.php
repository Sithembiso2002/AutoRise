<?php
declare(strict_types=1);

return [
    // 'mock' or 'stripe'
    'default' => env('PAYMENT_GATEWAY', 'mock'),

    'currency' => env('PAYMENT_CURRENCY', 'LSL'),

    'stripe' => [
        'public_key'     => env('STRIPE_PUBLIC_KEY', ''),
        'secret_key'     => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

    // Mock gateway controls (dev only)
    'mock' => [
        // Force a specific outcome: 'success', 'failure', or '' for random
        'force_outcome' => env('MOCK_PAYMENT_OUTCOME', ''),
    ],
];