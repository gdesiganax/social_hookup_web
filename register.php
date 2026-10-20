<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $displayName = text_input('display_name', 120);
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', text_input('username', 40));
    $age = int_input('age');
    $city = text_input('city', 120);
    $gender = in_array(($_POST['gender'] ?? ''), ['female', 'male', 'non_binary'], true) ? (string) $_POST['gender'] : '';
    $lookingFor = in_array(($_POST['looking_for'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) $_POST['looking_for'] : 'everyone';

    if (!$email || strlen($password) < 8 || $displayName === '' || $username === '' || $age < 18 || $gender === '') {
        flash('danger', 'Please complete the form. Password must be 8+ characters and age must be 18+.');
    } else {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare('INSERT INTO users (email, password_hash, role, status) VALUES (:email, :hash, "member", "active")');
            $stmt->execute([
                'email' => mb_strtolower($email),
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $userId = (int) db()->lastInsertId();
            $profile = db()->prepare(
                'INSERT INTO profiles (user_id, display_name, username, age, city, gender, looking_for, avatar_url, bio)
                 VALUES (:user_id, :display_name, :username, :age, :city, :gender, :looking_for, :avatar_url, :bio)'
            );
            $profile->execute([
                'user_id' => $userId,
                'display_name' => $displayName,
                'username' => $username,
                'age' => $age,
                'city' => $city,
                'gender' => $gender,
                'looking_for' => $lookingFor,
                'avatar_url' => 'https://i.pravatar.cc/150?u=' . rawurlencode((string) $email),
                'bio' => 'New here and ready to meet interesting people.',
            ]);
            db()->prepare('INSERT INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => $userId]);
            db()->commit();
            login_user($email, $password);
            flash('success', 'Your account is ready.');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            db()->rollBack();
            flash('danger', 'That email or username is already registered.');
        }
    }
}

$pageTitle = 'Create account';
require APP_ROOT . '/app/layout-header.php';
?>

<div class="auth-shell">
  <section class="auth-visual register">
    <div class="auth-visual-copy">
      <span class="badge rounded-pill bg-light text-dark mb-3">Nearby discovery</span>
      <h1 class="display-6 fw-bold mb-2">Build a profile people actually want to open.</h1>
      <p class="lead mb-0">Tell us who you are, who you want to meet, and we will prioritize nearby profiles.</p>
    </div>
  </section>
  <section class="auth-panel">
    <h2 class="h3 fw-bold mb-1">Create account</h2>
    <p class="text-muted">Join HookUp and start meeting people.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="display_name">Display name</label>
          <input class="form-control" name="display_name" id="display_name" required maxlength="120">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="username">Username</label>
          <input class="form-control" name="username" id="username" required maxlength="40">
        </div>
        <div class="col-md-8">
          <label class="form-label fw-semibold" for="email">Email</label>
          <input class="form-control" type="email" name="email" id="email" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="age">Age</label>
          <input class="form-control" type="number" name="age" id="age" min="18" max="120" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" for="city">City</label>
          <input class="form-control" name="city" id="city" maxlength="120" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="gender">I am</label>
          <select class="form-select" name="gender" id="gender" required>
            <option value="">Choose</option>
            <option value="female">Female</option>
            <option value="male">Male</option>
            <option value="non_binary">Non-binary</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="looking_for">I want to meet</label>
          <select class="form-select" name="looking_for" id="looking_for" required>
            <option value="female">Women</option>
            <option value="male">Men</option>
            <option value="everyone" selected>Everyone</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold" for="password">Password</label>
          <input class="form-control" type="password" name="password" id="password" minlength="8" autocomplete="new-password" required>
        </div>
      </div>
      <button class="btn btn-primary w-100 rounded-pill mt-4">Create account</button>
    </form>
    <p class="text-center text-muted mt-3 mb-0">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
  </section>
</div>

<?php require APP_ROOT . '/app/layout-footer.php'; ?>
