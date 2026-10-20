<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_auth();
$profile = get_profile((int) $user['id']);
$interests = get_all_interests();
$selectedInterests = get_profile_interests((int) $user['id']);
$mediaItems = get_profile_media((int) $user['id'], true);
$wallet = ensure_wallet_account((int) $user['id']);
$walletTransactions = get_wallet_transactions((int) $user['id'], 5);
$verification = get_user_verification((int) $user['id']);
$isVerified = $verification && $verification['overall_status'] === 'verified';

$pageTitle = 'Profile';
$activePage = 'profile';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="profile-summary-card text-center">
      <?= profile_avatar($profile, 'profile-summary-avatar', ['alt' => $profile['display_name'], 'size' => 128, 'wrapper_class' => 'avatar-stack-center mx-auto mb-3']) ?>
      <h1 class="h4 fw-bold mb-1">
        <?= e($profile['display_name']) ?>
        <?php if ($isVerified): ?><span class="verified-badge"><i class="bi bi-patch-check-fill"></i>Verified</span><?php endif; ?>
      </h1>
      <p class="text-muted mb-3">@<?= e($profile['username']) ?> · <?= (int) $profile['age'] ?> · <?= e($profile['city']) ?></p>
      <div class="d-grid gap-2 mb-3">
        <a class="btn btn-outline-primary rounded-pill" href="<?= e(url('user.php?id=' . (int) $user['id'])) ?>">View personal page</a>
        <button class="btn btn-primary rounded-pill" type="button" data-bs-toggle="modal" data-bs-target="#verificationModal">
          <i class="bi bi-shield-check me-1"></i><?= $isVerified ? 'Verification approved' : 'Submit verification' ?>
        </button>
      </div>
      <?php if ($verification): ?>
        <div class="verification-status-card <?= e($verification['overall_status']) ?> mb-3">
          <strong><?= e(ucfirst($verification['overall_status'])) ?> verification</strong>
          <span>Phone: <?= e($verification['phone_status']) ?> | Address: <?= e($verification['address_status']) ?> | NIN: <?= e($verification['government_id_status']) ?></span>
          <?php if (!empty($verification['rejection_reason'])): ?><em><?= e($verification['rejection_reason']) ?></em><?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="d-flex justify-content-center gap-4 mb-4">
        <div><h2 class="h5 fw-bold mb-0"><?= table_count('matches', '(user_one_id = :a OR user_two_id = :b) AND status = "matched"', ['a' => $user['id'], 'b' => $user['id']]) ?></h2><small class="text-muted">Matches</small></div>
        <div><h2 class="h5 fw-bold mb-0"><?= table_count('posts', 'user_id = :id AND deleted_at IS NULL', ['id' => $user['id']]) ?></h2><small class="text-muted">Posts</small></div>
      </div>
    </div>

    <div class="profile-wallet-card mt-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <span class="tool-eyebrow mb-0">Wallet</span>
          <h2 class="h5 fw-bold mb-0">Current balance</h2>
        </div>
        <i class="bi bi-wallet2 profile-wallet-icon"></i>
      </div>
      <div class="wallet-metrics profile-wallet-metrics">
        <div><span>Cash</span><strong><?= e(money_naira((float) $wallet['cash_balance'])) ?></strong></div>
        <div><span>Gift</span><strong><?= e(money_naira((float) $wallet['gift_balance'])) ?></strong></div>
        <div><span>Pending</span><strong><?= e(money_naira((float) $wallet['pending_withdrawal'])) ?></strong></div>
      </div>
      <?php if ($walletTransactions): ?>
        <div class="mt-3">
          <?php foreach ($walletTransactions as $transaction): ?>
            <div class="wallet-ledger-row">
              <span><?= e($transaction['description']) ?></span>
              <strong><?= (float) $transaction['amount'] >= 0 ? '+' : '' ?><?= e(money_naira((float) $transaction['amount'])) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a class="btn btn-primary rounded-pill w-100 mt-3" href="<?= e(url('messages.php')) ?>"><i class="bi bi-plus-circle me-1"></i>Fund wallet</a>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card p-4">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-pencil me-2"></i>Edit profile</h2>
      <form method="post" action="<?= e(url('actions/update_profile.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="display_name">Display name</label>
            <input class="form-control" name="display_name" id="display_name" value="<?= e($profile['display_name']) ?>" maxlength="120" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="username">Username</label>
            <input class="form-control" name="username" id="username" value="<?= e($profile['username']) ?>" maxlength="40" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" for="age">Age</label>
            <input class="form-control" type="number" name="age" id="age" value="<?= (int) $profile['age'] ?>" min="18" max="120" required>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold" for="city">City</label>
            <input class="form-control" name="city" id="city" value="<?= e($profile['city']) ?>" maxlength="120" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="gender">I am</label>
            <select class="form-select" name="gender" id="gender" required>
              <?php foreach (['female' => 'Female', 'male' => 'Male', 'non_binary' => 'Non-binary'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($profile['gender'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="looking_for">I want to meet</label>
            <select class="form-select" name="looking_for" id="looking_for" required>
              <?php foreach (['female' => 'Women', 'male' => 'Men', 'everyone' => 'Everyone'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($profile['looking_for'] ?? 'everyone') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" for="profile_visibility">Who can view my personal page?</label>
            <select class="form-select" name="profile_visibility" id="profile_visibility" required>
              <?php foreach (['public' => 'Everyone', 'matches' => 'Matched users only', 'private' => 'Only me'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($profile['profile_visibility'] ?? 'public') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" for="avatar_url">Avatar URL</label>
            <input class="form-control" type="url" name="avatar_url" id="avatar_url" value="<?= e($profile['avatar_url']) ?>" maxlength="500">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" for="avatar_file">Upload avatar</label>
            <input class="form-control" type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="form-text">JPG, PNG, WEBP, or GIF. Uploading a file replaces the avatar URL.</div>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" for="bio">About me</label>
            <textarea class="form-control" name="bio" id="bio" rows="5" maxlength="1000" required><?= e($profile['bio']) ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Interests</label>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($interests as $interest): ?>
                <label class="btn btn-outline-primary rounded-pill">
                  <input class="form-check-input me-1" type="checkbox" name="interests[]" value="<?= (int) $interest['id'] ?>" <?= in_array($interest['name'], $selectedInterests, true) ? 'checked' : '' ?>>
                  <?= e($interest['name']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <button class="btn btn-primary rounded-pill px-4 mt-4">Save profile</button>
      </form>
    </div>

    <div class="card p-4 mt-4">
      <h2 class="h5 fw-bold mb-3"><i class="bi bi-images me-2"></i>Share image or video</h2>
      <form method="post" action="<?= e(url('actions/create_media.php')) ?>" class="row g-3" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="col-md-8">
          <label class="form-label fw-semibold" for="media_file">Media file</label>
          <input class="form-control" type="file" name="media_file" id="media_file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" required>
          <div class="form-text">Images up to 8MB. Videos up to 50MB.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="visibility">Visibility</label>
          <select class="form-select" name="visibility" id="visibility" required>
            <option value="public">Public</option>
            <option value="private">Private</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label fw-semibold" for="caption">Caption</label>
          <input class="form-control" name="caption" id="caption" maxlength="180">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-primary rounded-pill px-4 w-100">Upload media</button>
        </div>
      </form>

      <?php if ($mediaItems): ?>
        <div class="profile-media-grid mt-4">
          <?php foreach ($mediaItems as $item): ?>
            <div class="profile-media-card">
              <?php if ($item['media_type'] === 'video'): ?>
                <video src="<?= e($item['media_url']) ?>" controls preload="metadata"></video>
              <?php else: ?>
                <img src="<?= e($item['media_url']) ?>" alt="<?= e($item['caption'] ?: 'Profile media') ?>">
              <?php endif; ?>
              <div class="p-3">
                <strong><?= e($item['caption'] ?: ucfirst($item['media_type'])) ?></strong>
                <span class="badge bg-soft-purple text-dark ms-2"><?= e($item['visibility']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content verification-modal">
      <div class="modal-header">
        <div>
          <span class="tool-eyebrow mb-0">Account verification</span>
          <h2 class="modal-title h5 fw-bold" id="verificationModalLabel">Nigeria NIN verification</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= e(url('actions/submit_verification.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-body">
          <?php if ($isVerified): ?>
            <div class="alert alert-success mb-0">
              <i class="bi bi-patch-check-fill me-1"></i>Your phone, address, and Nigeria NIN document have been verified.
            </div>
          <?php else: ?>
            <div class="verification-step-grid mb-4">
              <div><span>1</span><strong>Phone number</strong><small>Provide a reachable Nigerian phone number.</small></div>
              <div><span>2</span><strong>Address</strong><small>Submit your current residential address.</small></div>
              <div><span>3</span><strong>NIN document</strong><small>Upload your Nigeria NIN document for admin review.</small></div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="phone_number">Phone number</label>
                <input class="form-control" name="phone_number" id="phone_number" value="<?= e($verification['phone_number'] ?? '') ?>" placeholder="+234..." maxlength="40" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="nin_number">Nigeria NIN number</label>
                <input class="form-control" name="nin_number" id="nin_number" value="<?= e($verification['nin_number'] ?? '') ?>" pattern="[0-9]{11}" inputmode="numeric" maxlength="11" placeholder="11 digits" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" for="address_line">Residential address</label>
                <input class="form-control" name="address_line" id="address_line" value="<?= e($verification['address_line'] ?? '') ?>" maxlength="255" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="verification_city">City</label>
                <input class="form-control" name="city" id="verification_city" value="<?= e($verification['city'] ?? $profile['city']) ?>" maxlength="120" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="verification_state">State</label>
                <input class="form-control" name="state" id="verification_state" value="<?= e($verification['state'] ?? '') ?>" maxlength="120" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" for="nin_document">Nigeria NIN document</label>
                <input class="form-control" type="file" name="nin_document" id="nin_document" accept="application/pdf,image/jpeg,image/png,image/webp" required>
                <div class="form-text">PDF up to 10MB, or JPG/PNG/WEBP up to 8MB. This file is private and only admins can review it.</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <?php if (!$isVerified): ?>
          <div class="modal-footer">
            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary rounded-pill px-4">Submit verification</button>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
