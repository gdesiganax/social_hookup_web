<?php
require __DIR__ . '/app/bootstrap.php';

$viewer = require_auth();
$profileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$profile = $profileId ? get_profile($profileId) : null;

if (!$profile) {
    http_response_code(404);
    exit('Profile not found.');
}

if (!can_view_profile_page($viewer, $profile)) {
    http_response_code(403);
    $pageTitle = 'Private profile';
    require APP_ROOT . '/app/layout-header.php';
    ?>
    <div class="card p-5 text-center">
      <h1 class="h4 fw-bold">This profile is private</h1>
      <p class="text-muted mb-0">The member controls who can view their personal page.</p>
    </div>
    <?php
    require APP_ROOT . '/app/layout-footer.php';
    exit;
}

$isOwner = (int) $viewer['id'] === (int) $profile['user_id'];
$mediaItems = get_profile_media((int) $profile['user_id'], $isOwner);
$interests = get_profile_interests((int) $profile['user_id']);
$verification = get_user_verification((int) $profile['user_id']);
$isVerified = $verification && $verification['overall_status'] === 'verified';
$pageTitle = $profile['display_name'];
$activePage = 'profile';
require APP_ROOT . '/app/layout-header.php';
?>

<section class="profile-hero-card mb-4">
  <?= profile_avatar($profile, 'profile-hero-avatar', ['alt' => $profile['display_name'], 'size' => 128]) ?>
  <div>
    <h1 class="display-6 fw-bold mb-1">
      <?= e($profile['display_name']) ?>, <?= (int) $profile['age'] ?>
      <?php if ($isVerified): ?><span class="verified-badge light"><i class="bi bi-patch-check-fill"></i>Verified</span><?php endif; ?>
    </h1>
    <p class="mb-2">@<?= e($profile['username']) ?> · <?= e($profile['city']) ?></p>
    <p class="mb-3"><?= e($profile['bio']) ?></p>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($interests as $interest): ?>
        <span class="badge bg-light text-dark px-3 py-2 rounded-pill"><?= e($interest) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (!$isOwner): ?>
    <form method="post" action="<?= e(url('actions/start_chat.php')) ?>" class="ms-auto">
      <?= csrf_field() ?>
      <input type="hidden" name="user_id" value="<?= (int) $profile['user_id'] ?>">
      <button class="btn btn-light rounded-pill px-4"><i class="bi bi-chat-dots me-1"></i>Message</button>
    </form>
  <?php endif; ?>
</section>

<section class="card p-4">
  <h2 class="h5 fw-bold mb-3"><i class="bi bi-images me-2"></i>Photos and videos</h2>
  <?php if ($mediaItems): ?>
    <div class="profile-media-grid">
      <?php foreach ($mediaItems as $item): ?>
        <article class="profile-media-card">
          <?php if ($item['media_type'] === 'video'): ?>
            <video src="<?= e($item['media_url']) ?>" controls preload="metadata"></video>
          <?php else: ?>
            <img src="<?= e($item['media_url']) ?>" alt="<?= e($item['caption'] ?: 'Profile media') ?>">
          <?php endif; ?>
          <div class="p-3">
            <strong><?= e($item['caption'] ?: ucfirst($item['media_type'])) ?></strong>
            <?php if ($isOwner): ?><span class="badge bg-soft-purple text-dark ms-2"><?= e($item['visibility']) ?></span><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-muted">No public media yet.</div>
  <?php endif; ?>
</section>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
