<?php
require __DIR__ . '/app/bootstrap.php';

$selectedGender = in_array(($_GET['meet'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) $_GET['meet'] : 'everyone';
$selectedCity = trim((string) ($_GET['city'] ?? ''));
$profiles = get_discovery_profiles($selectedGender, $selectedCity, 10);
$authUser = current_user();

$pageTitle = 'Meet nearby people';
$activePage = 'home';
$bodyClass = 'home-page';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="home-discovery-layout">
  <section class="discovery-top">
    <div class="discovery-copy">
      <span class="tool-eyebrow"><i class="bi bi-broadcast-pin"></i> Live discovery</span>
      <h1 class="h2 fw-bold mb-2">Meet nearby profiles</h1>
      <p class="text-muted mb-0">Filter who you want to meet, then swipe through the people closest to your vibe.</p>
    </div>

    <form class="discovery-filter-bar" method="get">
      <div>
        <label class="form-label small fw-semibold" for="meet">I want to meet</label>
        <select class="form-select" name="meet" id="meet">
          <option value="female" <?= $selectedGender === 'female' ? 'selected' : '' ?>>Women</option>
          <option value="male" <?= $selectedGender === 'male' ? 'selected' : '' ?>>Men</option>
          <option value="everyone" <?= $selectedGender === 'everyone' ? 'selected' : '' ?>>Everyone</option>
        </select>
      </div>
      <div>
        <label class="form-label small fw-semibold" for="city">Near</label>
        <input class="form-control" name="city" id="city" value="<?= e($selectedCity) ?>" placeholder="City or area">
      </div>
      <button class="btn btn-primary rounded-pill w-100"><i class="bi bi-sliders me-1"></i>Update discovery</button>
    </form>

    <div class="discovery-side-stats">
      <div><strong><?= count($profiles) ?> <small>Profiles loaded</small></strong></div>
      <div><strong><?= e($selectedGender === 'everyone' ? 'All' : ucfirst($selectedGender)) ?> <small>Preference</small></strong></div>
      <div><strong><?= e($selectedCity !== '' ? $selectedCity : 'Any city') ?> <small>Location</small></strong></div>
    </div>
  </section>

  <section class="discover-band">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
      <div>
        <h2 class="h3 fw-bold mb-1">Swipe nearby profiles</h2>
        <div class="text-muted">Showing <?= e($selectedGender === 'everyone' ? 'everyone' : $selectedGender) ?><?= $selectedCity !== '' ? ' near ' . e($selectedCity) : '' ?>.</div>
      </div>
      <div class="swipe-controls">
        <button class="btn btn-light rounded-circle" type="button" data-swipe-prev title="Previous"><i class="bi bi-arrow-left"></i></button>
        <button class="btn btn-primary rounded-circle" type="button" data-swipe-next title="Next"><i class="bi bi-arrow-right"></i></button>
      </div>
    </div>

    <div class="swipe-stage" data-swipe-stage tabindex="0" aria-label="Swipe profile deck">
      <?php foreach ($profiles as $index => $profile): ?>
        <article class="swipe-card <?= $index === 0 ? 'active' : '' ?>" data-swipe-card data-index="<?= (int) $index ?>">
          <img src="<?= e($profile['avatar_url']) ?>" alt="<?= e($profile['display_name']) ?>">
          <?php if (profile_is_verified($profile) || profile_is_online($profile)): ?>
            <div class="swipe-card-badges" aria-label="Profile status">
              <?php if (profile_is_verified($profile)): ?><span class="avatar-verified-badge" title="Verified"><i class="bi bi-patch-check-fill"></i></span><?php endif; ?>
              <?php if (profile_is_online($profile)): ?><span class="avatar-online-dot" title="Online"></span><?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="swipe-stamp swipe-stamp-like">Like</div>
          <div class="swipe-stamp swipe-stamp-nope">Skip</div>
          <div class="swipe-card-body">
            <div>
              <h3 class="h2 fw-bold mb-1"><?= e($profile['display_name']) ?>, <?= (int) $profile['age'] ?></h3>
              <div class="mb-2"><i class="bi bi-geo-alt me-1"></i><?= e($profile['city']) ?> - <?= e(ucfirst(str_replace('_', '-', $profile['gender'] ?? 'member'))) ?></div>
              <p><?= e(mb_substr($profile['bio'], 0, 150)) ?></p>
            </div>
            <div class="d-flex gap-2 swipe-card-actions">
              <button class="btn btn-light rounded-circle swipe-action" type="button" data-swipe-skip title="Skip"><i class="bi bi-x-lg"></i></button>
              <?php if ($authUser): ?>
                <form method="post" action="<?= e(url('actions/match.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int) $profile['user_id'] ?>">
                  <button class="btn btn-primary rounded-circle swipe-action" title="Match"><i class="bi bi-heart-fill"></i></button>
                </form>
              <?php else: ?>
                <a class="btn btn-primary rounded-circle swipe-action" href="<?= e(url('register.php')) ?>" title="Join to match"><i class="bi bi-heart-fill"></i></a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (!$profiles): ?>
        <div class="card p-4 text-center">
          <h3 class="h5 fw-bold">No profiles found</h3>
          <p class="text-muted mb-0">Try another city or choose everyone.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
