<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_admin();
$search = trim((string) ($_GET['search'] ?? ''));
$type = in_array(($_GET['type'] ?? ''), ['seed_credit', 'wallet_funding', 'gift_sent', 'gift_received', 'gift_conversion', 'withdrawal_request'], true) ? (string) $_GET['type'] : '';
$balanceType = in_array(($_GET['balance_type'] ?? ''), ['cash', 'gift', 'pending_withdrawal'], true) ? (string) $_GET['balance_type'] : '';
$topupStatus = in_array(($_GET['topup_status'] ?? ''), ['pending', 'success', 'failed'], true) ? (string) $_GET['topup_status'] : '';

$transactions = admin_wallet_transactions($search, $type, $balanceType);
$topups = admin_wallet_topups($search, $topupStatus);

$pageTitle = 'Admin transactions';
$activePage = 'admin-transactions';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h2 fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Transactions</h1>
    <p class="text-muted mb-0">Wallet ledger activity and Paystack funding attempts.</p>
  </div>
  <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('admin/payment-requests.php')) ?>"><i class="bi bi-cash-coin me-1"></i>Payment requests</a>
</div>

<div class="card p-4 mb-4">
  <form class="row g-3 align-items-end" method="get">
    <div class="col-md-4">
      <label class="form-label fw-semibold" for="search">Search</label>
      <input type="text" class="form-control" name="search" id="search" value="<?= e($search) ?>" placeholder="User, email, reference, description">
    </div>
    <div class="col-md-3">
      <label class="form-label fw-semibold" for="type">Transaction type</label>
      <select class="form-select" name="type" id="type">
        <option value="">All ledger types</option>
        <?php foreach (['seed_credit', 'wallet_funding', 'gift_sent', 'gift_received', 'gift_conversion', 'withdrawal_request'] as $option): ?>
          <option value="<?= e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold" for="balance_type">Balance</label>
      <select class="form-select" name="balance_type" id="balance_type">
        <option value="">All</option>
        <?php foreach (['cash', 'gift', 'pending_withdrawal'] as $option): ?>
          <option value="<?= e($option) ?>" <?= $balanceType === $option ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label fw-semibold" for="topup_status">Topups</label>
      <select class="form-select" name="topup_status" id="topup_status">
        <option value="">All</option>
        <?php foreach (['pending', 'success', 'failed'] as $option): ?>
          <option value="<?= e($option) ?>" <?= $topupStatus === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1 d-grid">
      <button class="btn btn-primary rounded-pill" title="Filter"><i class="bi bi-search"></i></button>
    </div>
  </form>
</div>

<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h2 class="h5 fw-bold mb-0"><i class="bi bi-journal-text me-2"></i>Wallet ledger</h2>
    <span class="text-muted small">Showing <?= count($transactions) ?> entries</span>
  </div>
  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th>Type</th>
          <th>Balance</th>
          <th>Description</th>
          <th>Related user</th>
          <th class="text-end">Amount</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $transaction): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <?= profile_avatar($transaction, '', ['alt' => $transaction['display_name'] ?: $transaction['email'], 'size' => 36]) ?>
                <div><strong><?= e($transaction['display_name'] ?: $transaction['email']) ?></strong><br><span class="small text-muted">@<?= e($transaction['username'] ?: 'pending') ?></span></div>
              </div>
            </td>
            <td><span class="badge bg-light text-dark status-badge"><?= e(ucwords(str_replace('_', ' ', $transaction['transaction_type']))) ?></span></td>
            <td><?= e(ucwords(str_replace('_', ' ', $transaction['balance_type']))) ?></td>
            <td><?= e($transaction['description']) ?></td>
            <td><?= e($transaction['related_display_name'] ?: ($transaction['related_email'] ?: '-')) ?></td>
            <td class="text-end fw-semibold <?= (float) $transaction['amount'] < 0 ? 'text-danger' : 'text-success' ?>">
              <?= (float) $transaction['amount'] >= 0 ? '+' : '' ?><?= e(money_naira((float) $transaction['amount'])) ?>
            </td>
            <td><?= e(date('M j, Y g:ia', strtotime($transaction['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No wallet transactions match this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card p-4">
  <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h2 class="h5 fw-bold mb-0"><i class="bi bi-credit-card-2-front me-2"></i>Paystack topups</h2>
    <span class="text-muted small">Showing <?= count($topups) ?> attempts</span>
  </div>
  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th>Reference</th>
          <th>Status</th>
          <th class="text-end">Credit</th>
          <th class="text-end">Fee</th>
          <th class="text-end">Paid</th>
          <th>Provider</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topups as $topup): ?>
          <?php
            $statusClass = $topup['status'] === 'success' ? 'bg-success' : ($topup['status'] === 'failed' ? 'bg-danger' : 'bg-warning text-dark');
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <?= profile_avatar($topup, '', ['alt' => $topup['display_name'] ?: $topup['email'], 'size' => 36]) ?>
                <div><strong><?= e($topup['display_name'] ?: $topup['email']) ?></strong><br><span class="small text-muted">@<?= e($topup['username'] ?: 'pending') ?></span></div>
              </div>
            </td>
            <td><code><?= e($topup['reference']) ?></code></td>
            <td><span class="badge <?= e($statusClass) ?> status-badge"><?= e($topup['status']) ?></span></td>
            <td class="text-end"><?= e(money_naira((float) $topup['amount'])) ?></td>
            <td class="text-end"><?= e(money_naira((float) $topup['fee_amount'])) ?></td>
            <td class="text-end fw-semibold"><?= e(money_naira((float) $topup['payable_amount'])) ?></td>
            <td><?= e($topup['paystack_status'] ?: '-') ?></td>
            <td><?= e(date('M j, Y g:ia', strtotime($topup['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topups): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No Paystack topups match this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
