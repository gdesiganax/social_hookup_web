<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_admin();
$search = trim((string) ($_GET['search'] ?? ''));
$status = in_array(($_GET['status'] ?? ''), ['pending', 'approved', 'rejected', 'paid'], true) ? (string) $_GET['status'] : '';
$requests = admin_payment_requests($search, $status);
$counts = admin_payment_request_counts();

$pageTitle = 'Payment requests';
$activePage = 'admin-payment-requests';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h2 fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Payment requests</h1>
    <p class="text-muted mb-0">Review withdrawal requests, release rejected funds, and mark completed payouts.</p>
  </div>
  <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('admin/transactions.php')) ?>"><i class="bi bi-receipt-cutoff me-1"></i>Transactions</a>
</div>

<div class="row g-4 mb-4">
  <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $key => $label): ?>
    <div class="col-sm-6 col-lg-3">
      <div class="card metric-card p-3 text-center">
        <i class="bi <?= $key === 'pending' ? 'bi-hourglass-split text-warning' : ($key === 'approved' ? 'bi-check2-circle text-primary' : ($key === 'paid' ? 'bi-bank text-success' : 'bi-x-circle text-danger')) ?> fs-2"></i>
        <h2 class="fw-bold mt-2"><?= (int) $counts[$key] ?></h2>
        <span class="text-muted"><?= e($label) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card p-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h2 class="h5 fw-bold mb-0"><i class="bi bi-list-check me-2"></i>Withdrawal queue</h2>
    <form class="d-flex flex-wrap gap-2" method="get">
      <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search requests">
      <select class="form-select" name="status">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'paid', 'rejected'] as $option): ?>
          <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary rounded-pill" title="Filter"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th class="text-end">Amount</th>
          <th>Payout method</th>
          <th>Details</th>
          <th>Status</th>
          <th>Requested</th>
          <th>Wallet</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $request): ?>
          <?php
            $statusClass = 'bg-warning text-dark';
            if ($request['status'] === 'paid') {
                $statusClass = 'bg-success';
            } elseif ($request['status'] === 'approved') {
                $statusClass = 'bg-primary';
            } elseif ($request['status'] === 'rejected') {
                $statusClass = 'bg-danger';
            }
            $canReview = in_array($request['status'], ['pending', 'approved'], true);
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <?= profile_avatar($request, '', ['alt' => $request['display_name'] ?: $request['email'], 'size' => 36]) ?>
                <div><strong><?= e($request['display_name'] ?: $request['email']) ?></strong><br><span class="small text-muted">@<?= e($request['username'] ?: 'pending') ?></span></div>
              </div>
            </td>
            <td class="text-end fw-semibold"><?= e(money_naira((float) $request['amount'])) ?></td>
            <td><?= e($request['payout_method']) ?></td>
            <td class="small"><?= nl2br(e($request['payout_details'])) ?></td>
            <td>
              <span class="badge <?= e($statusClass) ?> status-badge"><?= e($request['status']) ?></span>
              <?php if ($request['reviewed_at']): ?>
                <div class="small text-muted mt-1"><?= e(date('M j, Y g:ia', strtotime($request['reviewed_at']))) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e(date('M j, Y g:ia', strtotime($request['requested_at']))) ?></td>
            <td class="small">
              Cash: <?= e(money_naira((float) $request['cash_balance'])) ?><br>
              Pending: <?= e(money_naira((float) $request['pending_withdrawal'])) ?>
            </td>
            <td class="text-end">
              <?php if ($canReview): ?>
                <?php if ($request['status'] === 'pending'): ?>
                  <form method="post" action="<?= e(url('actions/admin_payment_request.php')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-sm btn-success rounded-pill" title="Approve"><i class="bi bi-check2"></i></button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('actions/admin_payment_request.php')) ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                  <input type="hidden" name="action" value="paid">
                  <button class="btn btn-sm btn-primary rounded-pill" data-confirm="Mark this payout as paid?" title="Mark paid"><i class="bi bi-bank"></i></button>
                </form>
                <form method="post" action="<?= e(url('actions/admin_payment_request.php')) ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button class="btn btn-sm btn-outline-danger rounded-pill" data-confirm="Reject and return this amount to the user's cash wallet?" title="Reject"><i class="bi bi-x-lg"></i></button>
                </form>
              <?php else: ?>
                <span class="text-muted small">Closed</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$requests): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No payment requests match this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="text-muted small mt-2">Showing <?= count($requests) ?> requests.</div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
