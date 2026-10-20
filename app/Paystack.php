<?php

declare(strict_types=1);

function paystack_secret_key(): string
{
    return trim((string) config_value('paystack.secret_key', ''));
}

function paystack_fee_for(float $amount): float
{
    $percent = max(0.0, (float) config_value('paystack.transaction_charge_percent', 3));
    return round($amount * ($percent / 100), 2);
}

function paystack_request(string $method, string $endpoint, ?array $payload = null): array
{
    $secret = paystack_secret_key();
    if ($secret === '') {
        throw new RuntimeException('Paystack secret key is not configured.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required for Paystack payments.');
    }

    $ch = curl_init('https://api.paystack.co' . $endpoint);
    $headers = [
        'Authorization: Bearer ' . $secret,
        'Cache-Control: no-cache',
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        throw new RuntimeException($error ?: 'Paystack did not return a response.');
    }

    $response = json_decode($raw, true);
    if (!is_array($response)) {
        throw new RuntimeException('Paystack returned an invalid response.');
    }

    if ($status < 200 || $status >= 300 || empty($response['status'])) {
        throw new RuntimeException((string) ($response['message'] ?? 'Paystack request failed.'));
    }

    return $response;
}

function create_wallet_topup(array $user, float $amount): array
{
    $amount = round($amount, 2);
    if ($amount < 100) {
        throw new RuntimeException('Enter at least 100 naira to fund your wallet.');
    }

    $fee = paystack_fee_for($amount);
    $payable = round($amount + $fee, 2);
    $reference = 'wallet_' . (int) $user['id'] . '_' . bin2hex(random_bytes(10));

    $stmt = db()->prepare(
        'INSERT INTO wallet_topups (user_id, amount, fee_amount, payable_amount, reference, status)
         VALUES (:user_id, :amount, :fee_amount, :payable_amount, :reference, "pending")'
    );
    $stmt->execute([
        'user_id' => (int) $user['id'],
        'amount' => $amount,
        'fee_amount' => $fee,
        'payable_amount' => $payable,
        'reference' => $reference,
    ]);
    $topupId = (int) db()->lastInsertId();

    try {
        $response = paystack_request('POST', '/transaction/initialize', [
            'email' => (string) $user['email'],
            'amount' => (string) ((int) round($payable * 100)),
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => url('actions/paystack_callback.php'),
            'metadata' => [
                'purpose' => 'wallet_topup',
                'user_id' => (int) $user['id'],
                'wallet_credit_amount' => $amount,
                'paystack_fee_amount' => $fee,
            ],
        ]);
    } catch (Throwable $e) {
        db()->prepare('UPDATE wallet_topups SET status = "failed", paystack_status = :message WHERE id = :id')->execute([
            'message' => mb_substr($e->getMessage(), 0, 120),
            'id' => $topupId,
        ]);
        throw $e;
    }

    $data = $response['data'] ?? [];
    db()->prepare(
        'UPDATE wallet_topups
         SET access_code = :access_code, authorization_url = :authorization_url, provider_payload = :provider_payload
         WHERE id = :id'
    )->execute([
        'access_code' => (string) ($data['access_code'] ?? ''),
        'authorization_url' => (string) ($data['authorization_url'] ?? ''),
        'provider_payload' => json_encode($response),
        'id' => $topupId,
    ]);

    return [
        'id' => $topupId,
        'authorization_url' => (string) ($data['authorization_url'] ?? ''),
        'reference' => $reference,
    ];
}

function verify_paystack_wallet_topup(string $reference): bool
{
    $reference = trim($reference);
    if ($reference === '') {
        throw new RuntimeException('Payment reference is missing.');
    }

    $response = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
    $data = $response['data'] ?? [];
    $paystackStatus = (string) ($data['status'] ?? '');

    db()->beginTransaction();
    try {
        $stmt = db()->prepare('SELECT * FROM wallet_topups WHERE reference = :reference LIMIT 1 FOR UPDATE');
        $stmt->execute(['reference' => $reference]);
        $topup = $stmt->fetch();

        if (!$topup) {
            throw new RuntimeException('Wallet payment record was not found.');
        }

        if ($topup['status'] === 'success') {
            db()->commit();
            return false;
        }

        $expectedKobo = (int) round((float) $topup['payable_amount'] * 100);
        $paidKobo = (int) ($data['amount'] ?? 0);

        if ($paystackStatus !== 'success' || $paidKobo < $expectedKobo) {
            db()->prepare(
                'UPDATE wallet_topups SET status = "failed", paystack_status = :paystack_status, provider_payload = :payload WHERE id = :id'
            )->execute([
                'paystack_status' => $paystackStatus ?: 'not_successful',
                'payload' => json_encode($response),
                'id' => (int) $topup['id'],
            ]);
            db()->commit();
            return false;
        }

        db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute([
            'user_id' => (int) $topup['user_id'],
        ]);

        $walletStmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id LIMIT 1 FOR UPDATE');
        $walletStmt->execute(['user_id' => (int) $topup['user_id']]);
        $wallet = $walletStmt->fetch();
        if (!$wallet) {
            throw new RuntimeException('Wallet account could not be loaded.');
        }

        $creditAmount = (float) $topup['amount'];
        db()->prepare('UPDATE wallet_accounts SET cash_balance = cash_balance + :amount WHERE id = :id')->execute([
            'amount' => $creditAmount,
            'id' => (int) $wallet['id'],
        ]);

        db()->prepare(
            'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
             VALUES (:wallet_account_id, "wallet_funding", :amount, "cash", :description)'
        )->execute([
            'wallet_account_id' => (int) $wallet['id'],
            'amount' => $creditAmount,
            'description' => 'Wallet funded via Paystack (' . $reference . ')',
        ]);

        db()->prepare(
            'UPDATE wallet_topups
             SET status = "success", paystack_status = :paystack_status, provider_payload = :payload, paid_at = NOW()
             WHERE id = :id'
        )->execute([
            'paystack_status' => $paystackStatus,
            'payload' => json_encode($response),
            'id' => (int) $topup['id'],
        ]);

        db()->commit();
        return true;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}
