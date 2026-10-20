<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'conversation' && api_method() === 'GET') {
    $user = api_auth_user(true);
    $conversationId = api_int('conversation_id');
    if ($conversationId <= 0 || !user_can_access_conversation($conversationId, (int) $user['id'])) {
        api_error('Conversation not found.', 404);
    }
    api_json(['ok' => true, 'data' => get_conversation_gifts($conversationId, (int) $user['id'])]);
}

if ($action === 'send' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $input = api_input();
    $conversationId = (int) ($input['conversation_id'] ?? 0);
    $giftId = (int) ($input['gift_id'] ?? 0);
    $recipientId = (int) ($input['recipient_id'] ?? 0);

    if ($conversationId <= 0 || $giftId <= 0 || $recipientId <= 0 || $recipientId === (int) $user['id']) {
        api_error('Choose a valid gift and recipient.');
    }
    if (!user_can_access_conversation($conversationId, (int) $user['id']) || !user_can_access_conversation($conversationId, $recipientId)) {
        api_error('You can only send gifts inside your own conversations.', 403);
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

        db()->prepare('UPDATE wallet_accounts SET cash_balance = cash_balance - :amount WHERE id = :id')
            ->execute(['amount' => $price, 'id' => (int) $senderWallet['id']]);
        db()->prepare('UPDATE wallet_accounts SET gift_balance = gift_balance + :amount WHERE id = :id')
            ->execute(['amount' => $redeemable, 'id' => (int) $recipientWallet['id']]);

        db()->prepare(
            'INSERT INTO gift_purchases (conversation_id, gift_id, sender_id, recipient_id, price, redeemable_amount)
             VALUES (:conversation_id, :gift_id, :sender_id, :recipient_id, :price, :redeemable_amount)'
        )->execute([
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
        db()->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (:conversation_id, :sender_id, :body)')
            ->execute([
                'conversation_id' => $conversationId,
                'sender_id' => (int) $user['id'],
                'body' => '[gift:' . (int) $gift['id'] . '] Sent ' . $gift['name'] . ' to ' . ($recipientProfile['display_name'] ?? 'a match') . '.',
            ]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        api_error($e instanceof RuntimeException ? $e->getMessage() : 'Gift could not be sent.', 422);
    }

    api_json(['ok' => true, 'gift_purchase_id' => $purchaseId], 201);
}

api_handle_resource([
    'table' => 'gift_purchases',
    'primary_key' => 'id',
    'admin_only' => true,
    'filters' => ['id', 'conversation_id', 'gift_id', 'sender_id', 'recipient_id', 'status'],
    'owner_column' => 'sender_id',
    'create' => ['conversation_id', 'gift_id', 'sender_id', 'recipient_id', 'price', 'redeemable_amount', 'status'],
    'update' => ['status'],
]);
