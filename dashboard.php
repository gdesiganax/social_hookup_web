<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_auth();
$quickMatches = get_public_profiles((int) $user['id'], 8);
$postPageSize = 5;
$posts = get_posts($postPageSize + 1);
$hasMorePosts = count($posts) > $postPageSize;
$posts = array_slice($posts, 0, $postPageSize);
$dates = get_upcoming_dates((int) $user['id']);
$myInterests = get_profile_interests((int) $user['id']);

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require APP_ROOT . '/app/layout-header.php';
$discoverUrl = 'index.php?meet=' . rawurlencode((string) ($user['looking_for'] ?: 'everyone')) . '&city=' . rawurlencode((string) $user['city']);
?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4 p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick matches</h2>
        <a class="small text-decoration-none" href="<?= e(url($discoverUrl)) ?>">See all</a>
      </div>
      <div class="d-flex gap-3 overflow-auto pb-1">
        <?php foreach ($quickMatches as $match): ?>
          <a class="text-center text-decoration-none text-dark" href="<?= e(url('user.php?id=' . (int) $match['user_id'])) ?>">
            <?= profile_avatar($match, 'rounded-circle border border-2 border-white shadow-sm', ['alt' => $match['display_name'], 'size' => 60]) ?>
            <div class="small mt-1"><?= e(explode(' ', $match['display_name'])[0]) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card mb-4 p-4 post-composer">
      <div class="d-flex align-items-center gap-3 mb-3">
        <?= profile_avatar($user, 'profile-avatar', ['alt' => $user['display_name']]) ?>
        <div>
          <h2 class="h6 fw-bold mb-0">Share something with everyone</h2>
          <span class="text-muted small">General posts are visible to all signed-in members.</span>
        </div>
      </div>
      <form method="post" action="<?= e(url('actions/create_post.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <textarea class="form-control mb-3" name="body" rows="3" maxlength="1000" placeholder="Coffee plans, weekend ideas, a date spot you loved..." required></textarea>
        <div class="post-upload-row mb-3">
          <label class="post-upload-tile" for="post_media">
            <i class="bi bi-image"></i>
            <span>Add image or video</span>
          </label>
          <input class="form-control" type="file" name="post_media" id="post_media" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime">
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <span class="text-muted small"><i class="bi bi-shield-check me-1"></i>Be kind. Posts are moderated by admins.</span>
          <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-send me-1"></i>Post</button>
        </div>
      </form>
    </div>

    <div data-post-feed data-load-url="<?= e(url('actions/load_posts.php')) ?>" data-next-offset="<?= count($posts) ?>" data-page-size="<?= (int) $postPageSize ?>" data-has-more="<?= $hasMorePosts ? '1' : '0' ?>">
      <?php foreach ($posts as $post): ?>
        <?php require APP_ROOT . '/app/post-card.php'; ?>
      <?php endforeach; ?>
    </div>
    <div class="feed-loader text-center text-muted small py-3 <?= $hasMorePosts ? '' : 'd-none' ?>" data-post-loader>
      <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading more posts...
    </div>
    <div class="feed-end text-center text-muted small py-3 <?= $hasMorePosts ? 'd-none' : '' ?>" data-post-end>No more posts for now.</div>
  </div>

  <div class="col-lg-4">
    <div class="card p-4 mb-4">
      <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>Your profile</h2>
      <div class="d-flex align-items-center gap-3 mb-3">
        <?= profile_avatar($user, 'avatar-lg rounded-circle', ['alt' => 'My avatar', 'size' => 96]) ?>
        <div>
          <h3 class="h5 fw-bold mb-0"><?= e($user['display_name']) ?></h3>
          <span class="text-muted small">@<?= e($user['username']) ?> - <?= (int) $user['age'] ?></span>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach (array_slice($myInterests, 0, 5) as $interest): ?>
          <span class="badge bg-soft-purple text-dark px-3 py-2 rounded-pill"><?= e($interest) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="d-flex justify-content-between text-center border-top pt-3">
        <div><span class="fw-bold"><?= table_count('matches', '(user_one_id = :one OR user_two_id = :two) AND status = "matched"', ['one' => $user['id'], 'two' => $user['id']]) ?></span><br><span class="small text-muted">Matches</span></div>
        <div><span class="fw-bold"><?= table_count('post_reactions', 'user_id = :id', ['id' => $user['id']]) ?></span><br><span class="small text-muted">Likes</span></div>
        <div><span class="fw-bold"><?= table_count('messages', 'sender_id = :id', ['id' => $user['id']]) ?></span><br><span class="small text-muted">Messages</span></div>
      </div>
      <a class="btn btn-outline-primary w-100 mt-3 rounded-pill" href="<?= e(url('profile.php')) ?>">Edit profile</a>
    </div>

    <div class="card p-4">
      <h2 class="h6 fw-bold mb-3"><i class="bi bi-calendar-heart me-2"></i>Upcoming dates</h2>
      <div class="list-group list-group-flush">
        <?php foreach ($dates as $date): ?>
          <a class="list-group-item d-flex align-items-center gap-3 px-0 border-0 text-decoration-none text-dark" href="<?= e(url('messages.php')) ?>">
            <?= profile_avatar($date, 'rounded-circle', ['alt' => $date['display_name'], 'size' => 40]) ?>
            <div><strong><?= e($date['display_name']) ?></strong><br><span class="small text-muted"><?= e(date('D g:ia', strtotime($date['scheduled_at']))) ?> - <?= e($date['location']) ?></span></div>
          </a>
        <?php endforeach; ?>
        <?php if (!$dates): ?>
          <div class="text-muted small">No scheduled dates yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
