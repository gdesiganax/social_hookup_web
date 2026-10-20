<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        flash('danger', 'Enter a valid email and password.');
    } elseif (login_user($email, $password)) {
        redirect('dashboard.php');
    } else {
        flash('danger', 'The email or password is incorrect.');
    }
}

$pageTitle = 'Sign in';
$activePage = 'login';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="auth-shell">
  <section class="auth-visual">
    <div class="auth-visual-copy">
      <span class="badge rounded-pill bg-light text-dark mb-3">Private dating dashboard</span>
      <h1 class="display-6 fw-bold mb-2">Pick up the conversation where it started.</h1>
      <p class="lead mb-0">Your matches, messages, and nearby discoveries stay in one polished space.</p>
    </div>
  </section>
  <section class="auth-panel">
    <h2 class="h3 fw-bold mb-1">Welcome back</h2>
    <p class="text-muted">Sign in to your HookUp account.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold" for="email">Email</label>
        <input class="form-control" type="email" name="email" id="email" autocomplete="email" required>
      </div>
      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center gap-2">
          <label class="form-label fw-semibold" for="password">Password</label>
          <a class="small text-decoration-none" href="<?= e(url('reset-password.php')) ?>">Forgot password?</a>
        </div>
        <input class="form-control" type="password" name="password" id="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary w-100 rounded-pill">Sign in</button>
    </form>
    <p class="text-center text-muted mt-3 mb-0">No account yet? <a href="<?= e(url('register.php')) ?>">Create one</a></p>
  </section>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
