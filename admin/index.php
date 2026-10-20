<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_admin();
$stats = admin_stats();
$recentUsers = admin_users('', '');

$pageTitle = 'Admin dashboard';
$activePage = 'admin-dashboard';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h2 fw-bold"><i class="bi bi-speedometer2 me-2"></i>Admin dashboard</h1>
  <span class="badge bg-dark rounded-pill px-3 py-2">production backend</span>
</div>

<div class="row g-4 mb-5">
  <div class="col-md-3">
    <div class="card metric-card p-3 text-center">
      <i class="bi bi-people fs-2 text-primary"></i>
      <h2 class="fw-bold mt-2"><?= (int) $stats['users'] ?></h2>
      <span class="text-muted">Total users</span>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card metric-card p-3 text-center">
      <i class="bi bi-person-check fs-2 text-success"></i>
      <h2 class="fw-bold mt-2"><?= (int) $stats['active'] ?></h2>
      <span class="text-muted">Active</span>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card metric-card p-3 text-center">
      <i class="bi bi-heart-fill fs-2 text-danger"></i>
      <h2 class="fw-bold mt-2"><?= (int) $stats['matches'] ?></h2>
      <span class="text-muted">Matches</span>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card metric-card p-3 text-center">
      <i class="bi bi-flag fs-2 text-warning"></i>
      <h2 class="fw-bold mt-2"><?= (int) $stats['reports'] ?></h2>
      <span class="text-muted">Open reports</span>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card metric-card p-3 text-center">
      <i class="bi bi-cash-coin fs-2 text-primary"></i>
      <h2 class="fw-bold mt-2"><?= (int) $stats['pending_payments'] ?></h2>
      <span class="text-muted">Pending payments</span>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card p-4 h-100">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-activity me-2"></i>Recent users</h2>
      <div class="list-group list-group-flush">
        <?php foreach (array_slice($recentUsers, 0, 6) as $member): ?>
          <div class="list-group-item px-0 d-flex align-items-center gap-3">
            <?= profile_avatar($member, 'rounded-circle', ['alt' => $member['display_name'] ?: $member['email'], 'size' => 42]) ?>
            <div class="flex-grow-1">
              <strong><?= e($member['display_name'] ?: $member['email']) ?></strong><br>
              <span class="text-muted small"><?= e($member['email']) ?></span>
            </div>
            <span class="badge bg-light text-dark"><?= e($member['status']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-4 h-100">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-shield-check me-2"></i>Security controls</h2>
      <ul class="list-group list-group-flush">
        <li class="list-group-item px-0">PDO prepared statements with native prepares enabled</li>
        <li class="list-group-item px-0">CSRF tokens on every state-changing form</li>
        <li class="list-group-item px-0">Password hashing through PHP password API</li>
        <li class="list-group-item px-0">Role checks for all admin pages and admin actions</li>
        <li class="list-group-item px-0">Protected app/config/database folders via Apache rules</li>
      </ul>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-4 h-100">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i>Wallet operations</h2>
      <div class="d-grid gap-2">
        <a class="btn btn-outline-primary rounded-pill text-start" href="<?= e(url('admin/transactions.php')) ?>"><i class="bi bi-receipt-cutoff me-2"></i>Review all wallet transactions and Paystack topups</a>
        <a class="btn btn-outline-primary rounded-pill text-start" href="<?= e(url('admin/payment-requests.php')) ?>"><i class="bi bi-cash-coin me-2"></i>Approve, reject, or mark payment requests as paid</a>
      </div>
    </div>
  </div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
