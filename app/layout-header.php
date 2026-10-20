<?php
$authUser = current_user();
$pageTitle = $pageTitle ?? config_value('app.name', 'HookUp');
$activePage = $activePage ?? '';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> - <?= e(config_value('app.name', 'HookUp')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(public_path('assets/css/app.css?v=20261014a')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<nav class="navbar navbar-expand-lg app-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand app-brand" href="<?= e(url('index.php')) ?>">
      <span class="app-brand-mark"><i class="bi bi-hearts"></i></span>
      <span><?= e(config_value('app.name', 'HookUp')) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <?php if ($authUser): ?>
        <ul class="navbar-nav app-nav-links me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active-page' : '' ?>" href="<?= e(url('index.php')) ?>"><i class="bi bi-compass"></i><span>Home</span></a></li>
          <li class="nav-item"><a class="nav-link <?= $activePage === 'dashboard' ? 'active-page' : '' ?>" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-columns-gap"></i><span>Dashboard</span></a></li>
          <li class="nav-item"><a class="nav-link <?= $activePage === 'matches' ? 'active-page' : '' ?>" href="<?= e(url('matches.php')) ?>"><i class="bi bi-people-fill"></i><span>Matches</span></a></li>
          <li class="nav-item"><a class="nav-link <?= $activePage === 'messages' ? 'active-page' : '' ?>" href="<?= e(url('messages.php')) ?>"><i class="bi bi-chat-heart"></i><span>Messages</span></a></li>
          <li class="nav-item"><a class="nav-link <?= $activePage === 'profile' ? 'active-page' : '' ?>" href="<?= e(url('profile.php')) ?>"><i class="bi bi-person-badge"></i><span>Profile</span></a></li>
          <?php if (($authUser['role'] ?? '') === 'admin'): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle <?= strpos($activePage, 'admin') === 0 ? 'active-page' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-shield-lock"></i><span>Admin</span>
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>">Dashboard</a></li>
                <li><a class="dropdown-item" href="<?= e(url('admin/users.php')) ?>">Manage Users</a></li>
                <li><a class="dropdown-item" href="<?= e(url('admin/transactions.php')) ?>">Transactions</a></li>
                <li><a class="dropdown-item" href="<?= e(url('admin/payment-requests.php')) ?>">Payment Requests</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= e(url('admin/settings.php')) ?>">Settings</a></li>
              </ul>
            </li>
          <?php endif; ?>
        </ul>
        <div class="app-user-area">
          <a href="<?= e(url('profile.php')) ?>" class="app-user-chip">
            <?= profile_avatar($authUser, 'profile-avatar', ['alt' => 'My avatar']) ?>
            <span class="fw-semibold d-none d-lg-block"><?= e($authUser['display_name'] ?: $authUser['email']) ?></span>
          </a>
          <form method="post" action="<?= e(url('logout.php')) ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-outline-secondary btn-sm rounded-pill app-logout-btn" title="Logout"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
          </form>
        </div>
      <?php else: ?>
        <div class="ms-auto d-flex gap-2 app-guest-actions">
          <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('login.php')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</a>
          <a class="btn btn-primary rounded-pill" href="<?= e(url('register.php')) ?>"><i class="bi bi-person-plus me-1"></i>Create account</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container py-4">
<?php foreach (pull_flashes() as $type => $messages): ?>
  <?php foreach ($messages as $message): ?>
    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
      <?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>
