<?php

declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.*, p.display_name, p.username, p.age, p.city, p.bio, p.avatar_url,
                uv.overall_status AS verification_status
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE u.id = :id AND u.deleted_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        mark_user_seen((int) $row['id']);
        if (db_column_exists('users', 'last_seen_at')) {
            $row['last_seen_at'] = date('Y-m-d H:i:s');
        }
    }
    $user = $row ?: null;
    return $user;
}

function login_user(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['email' => mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    if ($user['status'] !== 'active') {
        flash('danger', 'This account is not active. Please contact support.');
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $seenColumn = db_column_exists('users', 'last_seen_at') ? ', last_seen_at = NOW()' : '';
    db()->prepare('UPDATE users SET last_login_at = NOW()' . $seenColumn . ' WHERE id = :id')->execute(['id' => $user['id']]);
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? 'member') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}
