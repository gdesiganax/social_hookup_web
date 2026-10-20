<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function api_json($data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $status = 400, array $extra = []): never
{
    api_json(['ok' => false, 'error' => $message] + $extra, $status);
}

function api_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function api_input(): array
{
    static $input = null;
    if (is_array($input)) {
        return $input;
    }

    $raw = file_get_contents('php://input') ?: '';
    $decoded = [];
    if ($raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $decoded = $json;
        } else {
            parse_str($raw, $decoded);
        }
    }

    $input = array_merge($_POST, $decoded);
    return $input;
}

function api_int(string $key, int $default = 0): int
{
    $source = array_merge($_GET, api_input());
    return filter_var($source[$key] ?? $default, FILTER_VALIDATE_INT) ?: $default;
}

function api_b64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function api_b64url_decode(string $value): string
{
    $padding = strlen($value) % 4;
    if ($padding) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/')) ?: '';
}

function api_token_secret(array $user): string
{
    return hash('sha256', (string) config_value('app.url', 'hookup') . '|' . (string) ($user['password_hash'] ?? ''));
}

function api_issue_token(array $user, int $days = 30): string
{
    $payload = [
        'uid' => (int) $user['id'],
        'exp' => time() + max(1, $days) * 86400,
        'nonce' => bin2hex(random_bytes(8)),
    ];
    $encoded = api_b64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $encoded, api_token_secret($user));
    return $encoded . '.' . $signature;
}

function api_bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', (string) $header, $matches)) {
        return trim($matches[1]);
    }
    return trim((string) ($_GET['token'] ?? ''));
}

function api_user_by_id(int $userId): ?array
{
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT u.*, p.display_name, p.username, p.age, p.city, p.bio, p.avatar_url,
                p.gender, p.looking_for, p.profile_visibility,
                uv.overall_status AS verification_status, ' . $presenceSelect . '
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE u.id = :id AND u.deleted_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function api_auth_user(bool $required = true): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $token = api_bearer_token();
    if ($token === '') {
        if ($required) {
            api_error('Authentication required.', 401);
        }
        $user = null;
        return null;
    }

    [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, '');
    $payload = json_decode(api_b64url_decode($encoded), true);
    if (!is_array($payload) || empty($payload['uid']) || empty($payload['exp']) || (int) $payload['exp'] < time()) {
        api_error('Invalid or expired token.', 401);
    }

    $loaded = api_user_by_id((int) $payload['uid']);
    if (!$loaded || ($loaded['status'] ?? '') !== 'active') {
        api_error('Account is not active.', 401);
    }

    $expected = hash_hmac('sha256', $encoded, api_token_secret($loaded));
    if (!hash_equals($expected, $signature)) {
        api_error('Invalid token signature.', 401);
    }

    mark_user_seen((int) $loaded['id']);
    if (db_column_exists('users', 'last_seen_at')) {
        $loaded['last_seen_at'] = date('Y-m-d H:i:s');
    }
    $user = $loaded;
    return $user;
}

function api_require_admin(): array
{
    $user = api_auth_user(true);
    if (($user['role'] ?? '') !== 'admin') {
        api_error('Admin access required.', 403);
    }
    return $user;
}

function api_public_user(array $user): array
{
    unset($user['password_hash']);
    return $user;
}

function api_is_admin(?array $user): bool
{
    return ($user['role'] ?? '') === 'admin';
}

