<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

if (api_method() === 'GET' && (string) ($_GET['active'] ?? '') === '1') {
    api_json(['ok' => true, 'data' => get_gift_catalog()]);
}

api_handle_resource([
    'table' => 'gift_catalog',
    'primary_key' => 'id',
    'public_get' => true,
    'filters' => ['id', 'is_active'],
    'create' => ['name', 'icon', 'price', 'redeemable_amount', 'is_active'],
    'update' => ['name', 'icon', 'price', 'redeemable_amount', 'is_active'],
]);

