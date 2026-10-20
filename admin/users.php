<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_admin();
$search = trim((string) ($_GET['search'] ?? ''));
$status = in_array(($_GET['status'] ?? ''), ['active', 'inactive', 'suspended'], true) ? (string) $_GET['status'] : '';
$users = admin_users($search, $status);

$pageTitle = 'Manage users';
$activePage = 'admin-users';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="card p-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h4 fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>Manage registered users</h1>
    <form class="d-flex gap-2" method="get">
      <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search users">
      <select class="form-select" name="status">
        <option value="">All</option>
        <?php foreach (['active', 'inactive', 'suspended'] as $option): ?>
          <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary rounded-pill"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead class="table-light">
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Status</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Matches</th>
          <th>Verification</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $member): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center">
                <?= profile_avatar($member, '', ['alt' => $member['display_name'] ?: $member['email'], 'size' => 36]) ?>
                <div><strong><?= e($member['display_name'] ?: 'No profile') ?></strong><br><span class="small text-muted">@<?= e($member['username'] ?: 'pending') ?></span></div>
              </div>
            </td>
            <td><?= e($member['email']) ?></td>
            <td><span class="badge bg-light text-dark status-badge"><?= e($member['status']) ?></span></td>
            <td><?= e($member['role']) ?></td>
            <td><?= e(date('M j, Y', strtotime($member['created_at']))) ?></td>
            <td><?= (int) $member['match_count'] ?></td>
            <td>
              <?php if ($member['verification_id']): ?>
                <?php
                  $verificationStatus = (string) $member['verification_status'];
                  $badgeClass = $verificationStatus === 'verified' ? 'bg-success' : ($verificationStatus === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                ?>
                <span class="badge <?= e($badgeClass) ?> status-badge"><?= e($verificationStatus) ?></span>
                <div class="small text-muted mt-1">
                  <?= e($member['phone_number']) ?><br>
                  NIN: <?= e($member['nin_number']) ?>
                </div>
                <a class="small" href="<?= e(url('actions/verification_document.php?id=' . (int) $member['verification_id'])) ?>" target="_blank" rel="noopener">View NIN document</a>
                <?php if ($verificationStatus === 'pending'): ?>
                  <div class="d-flex flex-wrap gap-1 mt-2">
                    <form method="post" action="<?= e(url('actions/admin_user.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                      <input type="hidden" name="action" value="verify">
                      <button class="btn btn-sm btn-success rounded-pill">Approve</button>
                    </form>
                    <form method="post" action="<?= e(url('actions/admin_user.php')) ?>" class="d-flex gap-1">
                      <?= csrf_field() ?>
                      <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                      <input type="hidden" name="action" value="reject_verification">
                      <input class="form-control form-control-sm admin-reject-input" name="rejection_reason" placeholder="Reason">
                      <button class="btn btn-sm btn-outline-danger rounded-pill">Reject</button>
                    </form>
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge bg-light text-dark status-badge">not submitted</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <form method="post" action="<?= e(url('actions/admin_user.php')) ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                <input type="hidden" name="action" value="<?= $member['status'] === 'active' ? 'suspend' : 'activate' ?>">
                <button class="btn btn-sm btn-light rounded-pill" title="<?= $member['status'] === 'active' ? 'Suspend' : 'Activate' ?>"><i class="bi bi-slash-circle"></i></button>
              </form>
              <form method="post" action="<?= e(url('actions/admin_user.php')) ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-light rounded-pill text-danger" data-confirm="Delete this user?" title="Delete"><i class="bi bi-trash3"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="text-muted small mt-2">Showing <?= count($users) ?> users.</div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
