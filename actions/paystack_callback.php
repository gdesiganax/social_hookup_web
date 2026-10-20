<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_auth();

$reference = trim((string) ($_GET['reference'] ?? ''));

try {
    $credited = verify_paystack_wallet_topup($reference);
    flash('success', $credited ? 'Wallet funded successfully.' : 'Payment already processed or not successful yet.');
} catch (Throwable $e) {
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Payment verification failed.');
}

redirect('messages.php');
