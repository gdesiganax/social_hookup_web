<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$amount = round((float) ($_POST['amount'] ?? 0), 2);
$conversationId = int_input('conversation_id');
$redirectBack = 'messages.php' . ($conversationId ? '?conversation_id=' . $conversationId : '');

try {
    $topup = create_wallet_topup($user, $amount);
    if ($topup['authorization_url'] === '') {
        throw new RuntimeException('Paystack did not return a checkout URL.');
    }
    header('Location: ' . $topup['authorization_url']);
    exit;
} catch (Throwable $e) {
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Wallet funding could not be started.');
    redirect($redirectBack);
}
