<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_admin();

$pageTitle = 'Admin settings';
$activePage = 'admin-settings';
require APP_ROOT . '/app/layout-header.php';
?>

<h1 class="h2 fw-bold mb-4"><i class="bi bi-gear me-2"></i>Admin settings</h1>
<div class="card p-4">
  <form method="post" action="<?= e(url('actions/admin_settings.php')) ?>">
    <?= csrf_field() ?>
    <div class="mb-4">
      <label class="form-label fw-semibold" for="site_name">Site name</label>
      <input type="text" class="form-control" name="site_name" id="site_name" value="<?= e(get_setting('site_name', config_value('app.name', 'HookUp'))) ?>" maxlength="120" required>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" for="support_email">Support email</label>
      <input type="email" class="form-control" name="support_email" id="support_email" value="<?= e(get_setting('support_email', 'support@example.com')) ?>" required>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold" for="default_user_status">Default user status</label>
      <select class="form-select" name="default_user_status" id="default_user_status">
        <?php foreach (['active', 'inactive'] as $option): ?>
          <option value="<?= e($option) ?>" <?= get_setting('default_user_status', 'active') === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-check form-switch mb-4">
      <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="maintenanceMode" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
      <label class="form-check-label" for="maintenanceMode">Enable maintenance mode</label>
    </div>
    <button class="btn btn-primary rounded-pill px-4">Save settings</button>
  </form>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
