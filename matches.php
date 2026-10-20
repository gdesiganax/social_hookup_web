<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_auth();
$search = trim((string) ($_GET['search'] ?? ''));
$profiles = get_matched_profiles((int) $user['id'], $search);

$pageTitle = 'Matches';
$activePage = 'matches';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 fw-bold mb-0"><i class="bi bi-people-fill me-2 brand-icon"></i>Your Matches</h1>
    <div class="text-muted small">People you have matched with. Use Home to discover more nearby profiles.</div>
  </div>
  <form class="d-flex gap-2" method="get">
    <input class="form-control rounded-pill" name="search" value="<?= e($search) ?>" placeholder="Search matches">
    <button class="btn btn-outline-primary rounded-pill"><i class="bi bi-search"></i></button>
  </form>
</div>

<div class="row g-4">
  <?php foreach ($profiles as $profile): ?>
    <div class="col-md-6 col-lg-4 col-xl-3">
      <div class="card p-4 text-center h-100">
        <?= profile_avatar($profile, 'rounded-circle', ['alt' => $profile['display_name'], 'size' => 90, 'wrapper_class' => 'avatar-stack-center mx-auto mb-3']) ?>
        <h2 class="h5 fw-bold mb-1"><?= e($profile['display_name']) ?></h2>
        <p class="text-muted small mb-2">@<?= e($profile['username']) ?> · <?= (int) $profile['age'] ?> · <?= e($profile['city']) ?></p>
        <p class="small mb-3"><?= e(mb_substr($profile['bio'], 0, 90)) ?></p>
        <div class="d-flex gap-2 mt-auto">
          <form method="post" action="<?= e(url('actions/start_chat.php')) ?>" class="flex-fill">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $profile['user_id'] ?>">
            <button class="btn btn-primary btn-sm rounded-pill w-100"><i class="bi bi-chat-dots me-1"></i>Message</button>
          </form>
          <span class="btn btn-outline-primary btn-sm rounded-pill disabled" title="Matched"><i class="bi bi-heart-fill"></i></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (!$profiles): ?>
    <div class="col-12">
      <div class="card p-4 text-center">
        <h2 class="h5 fw-bold">No matches yet</h2>
        <p class="text-muted mb-3">Visit the home page, choose who you want to meet, and start swiping nearby profiles.</p>
        <a class="btn btn-primary rounded-pill" href="<?= e(url('index.php')) ?>">Discover people</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
