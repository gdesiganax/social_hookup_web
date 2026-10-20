<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'request' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $input = api_input();
    $amount = (float) ($input['amount'] ?? 0);
    $payoutMethod = trim((string) ($input['payout_method'] ?? ''));
    $payoutDetails = trim((string) ($input['payout_details'] ?? ''));
    $wallet = ensure_wallet_account((int) $user['id']);

    if ($amount <= 0 || $payoutMethod === '' || $payoutDetails === '') {
        api_error('Amount and payout details are required.');
    }
    if ($amount > (float) $wallet['cash_balance']) {
        api_error('Insufficient cash balance.');
    }

    db()->beginTransaction();
    try {
        db()->prepare('UPDATE wallet_accounts SET cash_balance = cash_balance - :amount, pending_withdrawal = pending_withdrawal + :amount WHERE id = :id')
            ->execute(['amount' => $amount, 'id' => (int) $wallet['id']]);
        db()->prepare('INSERT INTO withdrawal_requests (user_id, wallet_account_id, amount, payout_method, payout_details) VALUES (:user_id, :wallet_account_id, :amount, :payout_method, :payout_details)')
            ->execute([
                'user_id' => (int) $user['id'],
                'wallet_account_id' => (int) $wallet['id'],
                'amount' => $amount,
                'payout_method' => $payoutMethod,
                'payout_details' => $payoutDetails,
            ]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        api_error('Withdrawal request failed.', 500);
    }

    api_json(['ok' => true, 'id' => (int) db()->lastInsertId()], 201);
}

api_handle_resource([
    'table' => 'withdrawal_requests',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id', 'wallet_account_id', 'status'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'wallet_account_id', 'amount', 'payout_method', 'payout_details', 'status'],
    'update' => ['status', 'reviewed_at'],
]);

