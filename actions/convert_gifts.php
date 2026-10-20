<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$amount = (float) ($_POST['amount'] ?? 0);
$conversationId = int_input('conversation_id');

if ($amount <= 0) {
    flash('danger', 'Enter a valid amount to convert.');
    redirect('messages.php' . ($conversationId ? '?conversation_id=' . $conversationId : ''));
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

    db()->prepare(
        'UPDATE wallet_accounts
         SET gift_balance = gift_balance - :gift_amount, cash_balance = cash_balance + :cash_amount
         WHERE id = :id'
    )->execute([
        'gift_amount' => $amount,
        'cash_amount' => $amount,
        'id' => (int) $wallet['id'],
    ]);

    $ledger = db()->prepare(
        'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
         VALUES (:wallet_account_id, :transaction_type, :amount, :balance_type, :description)'
    );
    $ledger->execute([
        'wallet_account_id' => (int) $wallet['id'],
        'transaction_type' => 'gift_conversion',
        'amount' => -$amount,
        'balance_type' => 'gift',
        'description' => 'Converted gifts to cash',
    ]);
    $ledger->execute([
        'wallet_account_id' => (int) $wallet['id'],
        'transaction_type' => 'gift_conversion',
        'amount' => $amount,
        'balance_type' => 'cash',
        'description' => 'Cash from gift conversion',
    ]);

    db()->commit();
    flash('success', 'Gift balance converted to cash.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Conversion failed.');
}

redirect('messages.php' . ($conversationId ? '?conversation_id=' . $conversationId : ''));
