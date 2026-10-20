<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$conversationId = int_input('conversation_id');
$giftId = int_input('gift_id');
$recipientId = int_input('recipient_id');

if ($conversationId <= 0 || $giftId <= 0 || $recipientId <= 0 || $recipientId === (int) $user['id']) {
    flash('danger', 'Choose a valid gift and recipient.');
    redirect('messages.php');
}

if (!user_can_access_conversation($conversationId, (int) $user['id']) || !user_can_access_conversation($conversationId, $recipientId)) {
    flash('danger', 'You can only send gifts inside your own conversations.');
    redirect('messages.php');
}

try {
    db()->beginTransaction();

    db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => (int) $user['id']]);
    db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => $recipientId]);

    $giftStmt = db()->prepare('SELECT * FROM gift_catalog WHERE id = :id AND is_active = 1 LIMIT 1');
    $giftStmt->execute(['id' => $giftId]);
    $gift = $giftStmt->fetch();

    if (!$gift) {
        throw new RuntimeException('Gift is unavailable.');
    }

    $senderWalletStmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id FOR UPDATE');
    $senderWalletStmt->execute(['user_id' => (int) $user['id']]);
    $senderWallet = $senderWalletStmt->fetch();

    $recipientWalletStmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id FOR UPDATE');
    $recipientWalletStmt->execute(['user_id' => $recipientId]);
    $recipientWallet = $recipientWalletStmt->fetch();

    $price = (float) $gift['price'];
    $redeemable = (float) $gift['redeemable_amount'];

    if ((float) $senderWallet['cash_balance'] < $price) {
        throw new RuntimeException('Insufficient wallet balance.');
    }

    db()->prepare('UPDATE wallet_accounts SET cash_balance = cash_balance - :amount WHERE id = :id')->execute([
        'amount' => $price,
        'id' => (int) $senderWallet['id'],
    ]);
    db()->prepare('UPDATE wallet_accounts SET gift_balance = gift_balance + :amount WHERE id = :id')->execute([
        'amount' => $redeemable,
        'id' => (int) $recipientWallet['id'],
    ]);

    $purchaseStmt = db()->prepare(
        'INSERT INTO gift_purchases (conversation_id, gift_id, sender_id, recipient_id, price, redeemable_amount)
         VALUES (:conversation_id, :gift_id, :sender_id, :recipient_id, :price, :redeemable_amount)'
    );
    $purchaseStmt->execute([
        'conversation_id' => $conversationId,
        'gift_id' => $giftId,
        'sender_id' => (int) $user['id'],
        'recipient_id' => $recipientId,
        'price' => $price,
        'redeemable_amount' => $redeemable,
    ]);
    $purchaseId = (int) db()->lastInsertId();

    $ledger = db()->prepare(
        'INSERT INTO wallet_transactions (wallet_account_id, related_user_id, gift_purchase_id, transaction_type, amount, balance_type, description)
         VALUES (:wallet_account_id, :related_user_id, :gift_purchase_id, :transaction_type, :amount, :balance_type, :description)'
    );
    $ledger->execute([
        'wallet_account_id' => (int) $senderWallet['id'],
        'related_user_id' => $recipientId,
        'gift_purchase_id' => $purchaseId,
        'transaction_type' => 'gift_sent',
        'amount' => -$price,
        'balance_type' => 'cash',
        'description' => 'Gift sent: ' . $gift['name'],
    ]);
    $ledger->execute([
        'wallet_account_id' => (int) $recipientWallet['id'],
        'related_user_id' => (int) $user['id'],
        'gift_purchase_id' => $purchaseId,
        'transaction_type' => 'gift_received',
        'amount' => $redeemable,
        'balance_type' => 'gift',
        'description' => 'Gift received: ' . $gift['name'],
    ]);

    $recipientProfile = get_profile($recipientId);
    db()->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (:conversation_id, :sender_id, :body)')->execute([
        'conversation_id' => $conversationId,
        'sender_id' => (int) $user['id'],
        'body' => '[gift:' . (int) $gift['id'] . '] Sent ' . $gift['name'] . ' to ' . ($recipientProfile['display_name'] ?? 'a match') . '.',
    ]);

    db()->commit();
    flash('success', 'Gift sent securely.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Gift could not be sent.');
}

redirect('messages.php?conversation_id=' . $conversationId);
