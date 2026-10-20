<?php

return [
    'app' => [
        'name' => 'HookUp',
        'env' => 'production',
        'url' => 'https://example.com',
        'timezone' => 'UTC',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'hookup',
        'user' => 'hookup_user',
        'pass' => 'use-a-long-random-password',
        'charset' => 'utf8mb4',
    ],
    'paystack' => [
        'public_key' => 'pk_live_or_test_key',
        'secret_key' => 'sk_live_or_test_key',
        'transaction_charge_percent' => 3,
    ],
];
