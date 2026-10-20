<?php

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'HookUp',
        'env' => getenv('APP_ENV') ?: 'development',
        'url' => getenv('APP_URL') ?: 'http://localhost/Hookup/',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Africa/Lagos',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_DATABASE') ?: 'hookup',
        'user' => getenv('DB_USERNAME') ?: 'root',
        'pass' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'paystack' => [
        'public_key' => getenv('PAYSTACK_PUBLIC_KEY') ?: '',
        'secret_key' => getenv('PAYSTACK_SECRET_KEY') ?: '',
        'transaction_charge_percent' => (float) (getenv('PAYSTACK_TRANSACTION_CHARGE_PERCENT') ?: 3),
    ],
];
