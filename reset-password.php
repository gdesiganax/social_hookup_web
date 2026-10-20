<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

    if (!$email || strlen($password) < 8 || $password !== $confirmPassword) {
        flash('danger', 'Enter a valid email and matching password with at least 8 characters.');
    } else {
        $stmt = db()->prepare(
            'UPDATE users
             SET password_hash = :hash, updated_at = NOW()
             WHERE email = :email AND status = "active" AND deleted_at IS NULL'
        );
        $stmt->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'email' => mb_strtolower($email),
        ]);

        flash('success', 'If that active account exists, the password has been updated. You can sign in now.');
        redirect('login.php');
    }
}

$pageTitle = 'Reset password';
$activePage = 'login';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="auth-shell">
  <section class="auth-visual">
    <div class="auth-visual-copy">
      <span class="badge rounded-pill bg-light text-dark mb-3">Account access</span>
      <h1 class="display-6 fw-bold mb-2">Choose a new password and get back in.</h1>
      <p class="lead mb-0">Use the email on your HookUp account and set a fresh password.</p>
    </div>
  </section>
  <section class="auth-panel">
    <h2 class="h3 fw-bold mb-1">Reset password</h2>
    <p class="text-muted">Enter your account email and a new password.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="email">Email</label>
        <input class="form-control" type="email" name="email" id="email" autocomplete="email" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="password">New password</label>
        <input class="form-control" type="password" name="password" id="password" minlength="8" autocomplete="new-password" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold" for="password_confirmation">Confirm password</label>
        <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" minlength="8" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary w-100 rounded-pill">Update password</button>
    </form>
    <p class="text-center text-muted mt-3 mb-0">Remembered it? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
  </section>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
