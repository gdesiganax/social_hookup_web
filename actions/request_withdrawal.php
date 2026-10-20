<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$amount = (float) ($_POST['amount'] ?? 0);
$method = text_input('payout_method', 80);
$details = text_input('payout_details', 255);
$conversationId = int_input('conversation_id');

if ($amount <= 0 || $method === '' || $details === '') {
    flash('danger', 'Enter withdrawal amount and payout details.');
    redirect('messages.php' . ($conversationId ? '?conversation_id=' . $conversationId : ''));
}

try {
    db()->beginTransaction();
    db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => (int) $user['id']]);

    $stmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id FOR UPDATE');
    $stmt->execute(['user_id' => (int) $user['id']]);
    $wallet = $stmt->fetch();

    if (!$wallet || (float) $wallet['cash_balance'] < $amount) {
        throw new RuntimeException('Insufficient cash balance.');
    }

    db()->prepare(
        'UPDATE wallet_accounts
         SET cash_balance = cash_balance - :cash_amount, pending_withdrawal = pending_withdrawal + :pending_amount
         WHERE id = :id'
    )->execute([
        'cash_amount' => $amount,
        'pending_amount' => $amount,
        'id' => (int) $wallet['id'],
    ]);

    db()->prepare(
        'INSERT INTO withdrawal_requests (user_id, wallet_account_id, amount, payout_method, payout_details)
         VALUES (:user_id, :wallet_account_id, :amount, :payout_method, :payout_details)'
    )->execute([
        'user_id' => (int) $user['id'],
        'wallet_account_id' => (int) $wallet['id'],
        'amount' => $amount,
        'payout_method' => $method,
        'payout_details' => $details,
    ]);

    db()->prepare(
        'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
         VALUES (:wallet_account_id, "withdrawal_request", :amount, "pending_withdrawal", :description)'
    )->execute([
        'wallet_account_id' => (int) $wallet['id'],
        'amount' => $amount,
        'description' => 'Withdrawal requested via ' . $method,
    ]);

    db()->commit();
    flash('success', 'Withdrawal request submitted for review.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Withdrawal request failed.');
}

redirect('messages.php' . ($conversationId ? '?conversation_id=' . $conversationId : ''));
