<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$payload = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');
$secret = paystack_secret_key();

if ($secret === '' || $payload === '' || $signature === '') {
    http_response_code(400);
    exit('Invalid webhook request.');
}

$hash = hash_hmac('sha512', $payload, $secret);
if (!hash_equals($hash, $signature)) {
    http_response_code(401);
    exit('Invalid signature.');
}

$event = json_decode($payload, true);
if (!is_array($event)) {
    http_response_code(400);
    exit('Invalid payload.');
}

if (($event['event'] ?? '') === 'charge.success') {
    $reference = (string) ($event['data']['reference'] ?? '');
    try {
        verify_paystack_wallet_topup($reference);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Webhook processing failed.');
    }
}

http_response_code(200);
echo 'OK';
