<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'login' && api_method() === 'POST') {
    $input = api_input();
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        api_error('Invalid email or password.', 401);
    }
    if ($user['status'] !== 'active') {
        api_error('This account is not active.', 403);
    }

    $seenColumn = db_column_exists('users', 'last_seen_at') ? ', last_seen_at = NOW()' : '';
    db()->prepare('UPDATE users SET last_login_at = NOW()' . $seenColumn . ' WHERE id = :id')->execute(['id' => (int) $user['id']]);
    $loaded = api_user_by_id((int) $user['id']) ?: $user;

    api_json(['ok' => true, 'token' => api_issue_token($user), 'user' => api_public_user($loaded)]);
}

if ($action === 'register' && api_method() === 'POST') {
    $input = api_input();
    $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $displayName = trim((string) ($input['display_name'] ?? ''));
    $username = trim((string) ($input['username'] ?? ''));
    $age = (int) ($input['age'] ?? 0);
    $city = trim((string) ($input['city'] ?? ''));
    $gender = in_array(($input['gender'] ?? ''), ['female', 'male', 'non_binary'], true) ? (string) $input['gender'] : 'female';
    $lookingFor = in_array(($input['looking_for'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) $input['looking_for'] : 'everyone';
    $bio = trim((string) ($input['bio'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $displayName === '' || $username === '' || $age < 18 || $city === '' || $bio === '') {
        api_error('Complete registration fields are required.');
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare('INSERT INTO users (email, password_hash, role, status) VALUES (:email, :hash, "member", "active")');
        $stmt->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
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
            'avatar_url' => trim((string) ($input['avatar_url'] ?? '')) ?: 'https://i.pravatar.cc/150?u=' . rawurlencode($email),
            'bio' => $bio,
        ]);

        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        api_error('Registration failed. Email or username may already exist.', 422);
    }

    $user = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $user->execute(['id' => $userId]);
    $created = $user->fetch();
    api_json(['ok' => true, 'token' => api_issue_token($created), 'user' => api_public_user(api_user_by_id($userId) ?: $created)], 201);
}

if ($action === 'me' && api_method() === 'GET') {
    api_json(['ok' => true, 'user' => api_public_user(api_auth_user(true))]);
}

api_handle_resource([
    'table' => 'users',
    'primary_key' => 'id',
    'admin_only' => true,
    'filters' => ['id', 'email', 'role', 'status'],
    'create' => [],
    'update' => ['role', 'status', 'email_verified_at'],
]);

