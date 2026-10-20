<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if (($action === 'mine' || api_method() === 'GET') && !api_int('id')) {
    $user = api_auth_user(true);
    if (api_is_admin($user) && api_int('wallet_account_id') > 0) {
        api_handle_resource([
            'table' => 'wallet_transactions',
            'primary_key' => 'id',
            'filters' => ['id', 'wallet_account_id', 'related_user_id', 'gift_purchase_id', 'transaction_type', 'balance_type'],
            'create' => ['wallet_account_id', 'related_user_id', 'gift_purchase_id', 'transaction_type', 'amount', 'balance_type', 'description'],
            'update' => ['description'],
        ]);
    }
    api_json(['ok' => true, 'data' => get_wallet_transactions((int) $user['id'], api_int('limit', 25))]);
}

api_handle_resource([
    'table' => 'wallet_transactions',
    'primary_key' => 'id',
    'admin_only' => true,
    'filters' => ['id', 'wallet_account_id', 'related_user_id', 'gift_purchase_id', 'transaction_type', 'balance_type'],
    'create' => ['wallet_account_id', 'related_user_id', 'gift_purchase_id', 'transaction_type', 'amount', 'balance_type', 'description'],
    'update' => ['description'],
]);

