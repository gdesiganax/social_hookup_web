<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_admin();
require_post();

$requestId = int_input('request_id');
$action = text_input('action', 20);

if ($requestId <= 0 || !in_array($action, ['approve', 'reject', 'paid'], true)) {
    flash('danger', 'Invalid payment request action.');
    redirect('admin/payment-requests.php');
}

db()->beginTransaction();

try {
    $stmt = db()->prepare(
        'SELECT wr.*, wa.pending_withdrawal
         FROM withdrawal_requests wr
         JOIN wallet_accounts wa ON wa.id = wr.wallet_account_id
         WHERE wr.id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute(['id' => $requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new RuntimeException('Payment request was not found.');
    }

    $amount = (float) $request['amount'];
    $currentStatus = (string) $request['status'];
    $walletAccountId = (int) $request['wallet_account_id'];

    if ($action === 'approve') {
        if ($currentStatus !== 'pending') {
            throw new RuntimeException('Only pending requests can be approved.');
        }

        db()->prepare('UPDATE withdrawal_requests SET status = "approved", reviewed_at = NOW() WHERE id = :id')->execute([
            'id' => $requestId,
        ]);
        flash('success', 'Payment request approved.');
    } elseif ($action === 'paid') {
        if (!in_array($currentStatus, ['pending', 'approved'], true)) {
            throw new RuntimeException('Only pending or approved requests can be marked paid.');
        }
        if ((float) $request['pending_withdrawal'] < $amount) {
            throw new RuntimeException('This wallet does not have enough pending withdrawal balance.');
        }

        db()->prepare(
            'UPDATE wallet_accounts
             SET pending_withdrawal = pending_withdrawal - :amount
             WHERE id = :id'
        )->execute([
            'amount' => $amount,
            'id' => $walletAccountId,
        ]);

        db()->prepare(
            'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
             VALUES (:wallet_account_id, "withdrawal_request", :amount, "pending_withdrawal", :description)'
        )->execute([
            'wallet_account_id' => $walletAccountId,
            'amount' => -$amount,
            'description' => 'Withdrawal paid by admin',
        ]);

        db()->prepare('UPDATE withdrawal_requests SET status = "paid", reviewed_at = NOW() WHERE id = :id')->execute([
            'id' => $requestId,
        ]);
        flash('success', 'Payment request marked as paid.');
    } elseif ($action === 'reject') {
        if (!in_array($currentStatus, ['pending', 'approved'], true)) {
            throw new RuntimeException('Only pending or approved requests can be rejected.');
        }
        if ((float) $request['pending_withdrawal'] < $amount) {
            throw new RuntimeException('This wallet does not have enough pending withdrawal balance.');
        }

        db()->prepare(
            'UPDATE wallet_accounts
             SET pending_withdrawal = pending_withdrawal - :pending_amount,
                 cash_balance = cash_balance + :cash_amount
             WHERE id = :id'
        )->execute([
            'pending_amount' => $amount,
            'cash_amount' => $amount,
            'id' => $walletAccountId,
        ]);

        $ledger = db()->prepare(
            'INSERT INTO wallet_transactions (wallet_account_id, transaction_type, amount, balance_type, description)
             VALUES (:wallet_account_id, "withdrawal_request", :amount, :balance_type, :description)'
        );
        $ledger->execute([
            'wallet_account_id' => $walletAccountId,
            'amount' => -$amount,
            'balance_type' => 'pending_withdrawal',
            'description' => 'Withdrawal request rejected by admin',
        ]);
        $ledger->execute([
            'wallet_account_id' => $walletAccountId,
            'amount' => $amount,
            'balance_type' => 'cash',
            'description' => 'Rejected withdrawal returned to cash wallet',
        ]);

        db()->prepare('UPDATE withdrawal_requests SET status = "rejected", reviewed_at = NOW() WHERE id = :id')->execute([
            'id' => $requestId,
        ]);
        flash('success', 'Payment request rejected and funds returned.');
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Payment request could not be updated.');
}

redirect('admin/payment-requests.php');
