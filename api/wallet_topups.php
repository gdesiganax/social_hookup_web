<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'mine' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => get_wallet_topups((int) $user['id'], api_int('limit', 20))]);
}

if ($action === 'fund' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $amount = (float) (api_input()['amount'] ?? 0);
    if ($amount < 100) {
        api_error('Minimum top-up is 100.');
    }
    if (paystack_secret_key() === '') {
        api_error('Paystack is not configured.', 503);
    }
    try {
        api_json(['ok' => true, 'data' => initialize_paystack_topup((int) $user['id'], $amount)], 201);
    } catch (Throwable $e) {
        api_error($e->getMessage(), 502);
    }
}

api_handle_resource([
    'table' => 'wallet_topups',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id', 'reference', 'status', 'paystack_status'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'amount', 'fee_amount', 'payable_amount', 'reference', 'access_code', 'authorization_url', 'status', 'paystack_status', 'provider_payload', 'paid_at'],
    'update' => ['status', 'paystack_status', 'provider_payload', 'paid_at'],
]);

