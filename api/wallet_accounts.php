<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'mine' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => ensure_wallet_account((int) $user['id'])]);
}

if ($action === 'summary' && api_method() === 'GET') {
    api_auth_user(true);
    $userId = api_int('user_id');
    if ($userId <= 0) {
        api_error('user_id is required.');
    }
    $wallet = ensure_wallet_account($userId);
    api_json(['ok' => true, 'data' => [
        'user_id' => (int) $wallet['user_id'],
        'cash_balance' => (float) $wallet['cash_balance'],
        'gift_balance' => (float) $wallet['gift_balance'],
        'pending_withdrawal' => (float) $wallet['pending_withdrawal'],
    ]]);
}

if ($action === 'convert_gifts' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $amount = (float) (api_input()['amount'] ?? 0);
    if ($amount <= 0) {
        api_error('Enter a valid amount to convert.');
    }

    try {
        db()->beginTransaction();
        db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => (int) $user['id']]);
        $stmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id FOR UPDATE');
        $stmt->execute(['user_id' => (int) $user['id']]);
        $wallet = $stmt->fetch();
        if (!$wallet || (float) $wallet['gift_balance'] < $amount) {
            throw new RuntimeException('Insufficient gift balance.');
        }

        db()->prepare('UPDATE wallet_accounts SET gift_balance = gift_balance - :amount, cash_balance = cash_balance + :amount WHERE id = :id')
            ->execute(['amount' => $amount, 'id' => (int) $wallet['id']]);
        $ledger = db()->prepare(
            'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
             VALUES (:wallet_account_id, "gift_conversion", :amount, :balance_type, :description)'
        );
        $ledger->execute(['wallet_account_id' => (int) $wallet['id'], 'amount' => -$amount, 'balance_type' => 'gift', 'description' => 'Converted gifts to cash']);
        $ledger->execute(['wallet_account_id' => (int) $wallet['id'], 'amount' => $amount, 'balance_type' => 'cash', 'description' => 'Cash from gift conversion']);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        api_error($e instanceof RuntimeException ? $e->getMessage() : 'Conversion failed.', 422);
    }

    api_json(['ok' => true, 'data' => ensure_wallet_account((int) $user['id'])]);
}

api_handle_resource([
    'table' => 'wallet_accounts',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'cash_balance', 'gift_balance', 'pending_withdrawal'],
    'update' => ['cash_balance', 'gift_balance', 'pending_withdrawal'],
]);
