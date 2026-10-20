<?php

declare(strict_types=1);

function config_value(string $path, $default = null)
{
    global $config;
    $value = $config;
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

function db(): PDO
{
    return Database::connection();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_local_request(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?: $host;
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function request_scheme(): string
{
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if (in_array($forwardedProto, ['http', 'https'], true)) {
        return $forwardedProto;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');
    return $isHttps ? 'https' : 'http';
}

function configured_app_url(): string
{
    return rtrim((string) config_value('app.url', ''), '/');
}

function configured_base_path(): string
{
    $path = (string) (parse_url(configured_app_url(), PHP_URL_PATH) ?: '');
    return '/' . trim($path, '/');
}

function detected_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $rootName = basename((string) (defined('APP_ROOT') ? APP_ROOT : ''));

    if ($rootName !== '') {
        $segments = array_values(array_filter(explode('/', trim($scriptName, '/')), 'strlen'));
        $position = array_search($rootName, $segments, true);
        if ($position !== false) {
            return '/' . implode('/', array_slice($segments, 0, $position + 1));
        }
    }

    $dir = trim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($dir === '' || $dir === '.') {
        return '';
    }

    foreach (['api', 'actions', 'admin'] as $appSubdir) {
        if ($dir === $appSubdir) {
            return '';
        }
        if (substr($dir, -strlen('/' . $appSubdir)) === '/' . $appSubdir) {
            return '/' . substr($dir, 0, -strlen('/' . $appSubdir));
        }
    }

    return '/' . $dir;
}

function app_base_path(): string
{
    $configuredPath = configured_base_path();
    $configuredHost = strtolower((string) (parse_url(configured_app_url(), PHP_URL_HOST) ?: ''));
    if (is_local_request()) {
        $detectedPath = detected_base_path();
        return $detectedPath !== '' ? $detectedPath : ($configuredPath === '/' ? '' : $configuredPath);
    }

    if (!empty($_SERVER['HTTP_HOST']) && in_array($configuredHost, ['', 'localhost', '127.0.0.1', '::1'], true)) {
        return detected_base_path();
    }

    return $configuredPath === '/' ? '' : $configuredPath;
}

function app_base_url(): string
{
    $configuredUrl = configured_app_url();
    $configuredHost = strtolower((string) (parse_url($configuredUrl, PHP_URL_HOST) ?: ''));
    $requestHost = (string) ($_SERVER['HTTP_HOST'] ?? '');

    if ($requestHost !== '' && (is_local_request() || in_array($configuredHost, ['', 'localhost', '127.0.0.1', '::1'], true))) {
        return request_scheme() . '://' . $requestHost . app_base_path();
    }

    return $configuredUrl;
}

function url(string $path = ''): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = app_base_url();
    $path = ltrim($path, '/');
    $fragment = '';
    $query = '';

    if (($hashPosition = strpos($path, '#')) !== false) {
        $fragment = substr($path, $hashPosition);
        $path = substr($path, 0, $hashPosition);
    }

    if (($queryPosition = strpos($path, '?')) !== false) {
        $query = substr($path, $queryPosition);
        $path = substr($path, 0, $queryPosition);
    }

    if ($path === 'index.php') {
        $path = '';
    } elseif (substr($path, -10) === '/index.php') {
        $path = substr($path, 0, -10);
    } elseif (substr($path, -4) === '.php') {
        $path = substr($path, 0, -4);
    }

    return $base . '/' . $path . $query . $fragment;
}

function public_path(string $path = ''): string
{
    $path = ltrim($path, '/');
    $basePath = app_base_path();
    return ($basePath === '' ? '' : $basePath) . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid security token. Please go back and try again.');
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    verify_csrf();
}

function int_input(string $name, int $default = 0): int
{
    return filter_input(INPUT_POST, $name, FILTER_VALIDATE_INT) ?: $default;
}

function text_input(string $name, int $max = 500): string
{
    $value = trim((string) ($_POST[$name] ?? ''));
    return mb_substr($value, 0, $max);
}

function table_count(string $table, string $where = '1=1', array $params = []): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $seconds = max(1, time() - strtotime($datetime));
    $units = [
        31536000 => 'y',
        2592000 => 'mo',
        604800 => 'w',
        86400 => 'd',
        3600 => 'h',
        60 => 'm',
    ];
    foreach ($units as $size => $label) {
        if ($seconds >= $size) {
            return (int) floor($seconds / $size) . $label;
        }
    }
    return 'now';
}

function money_naira(float $amount): string
{
    return '₦' . number_format($amount, 2);
}

function compact_count(int $count): string
{
    if ($count >= 1000000) {
        return rtrim(rtrim(number_format($count / 1000000, 1), '0'), '.') . 'M';
    }
    if ($count >= 1000) {
        return rtrim(rtrim(number_format($count / 1000, 1), '0'), '.') . 'K';
    }
    return (string) $count;
}

function db_column_exists(string $table, string $column): bool
{
    static $cache = [];

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = db()->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $stmt->execute(['column' => $column]);
        $cache[$key] = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function user_presence_column(): string
{
    return db_column_exists('users', 'last_seen_at') ? 'last_seen_at' : 'last_login_at';
}

function user_presence_select(string $alias = 'u', string $as = 'last_seen_at'): string
{
    $column = user_presence_column();
    return "{$alias}.{$column} AS {$as}";
}

function mark_user_seen(int $userId): void
{
    if ($userId <= 0 || !db_column_exists('users', 'last_seen_at')) {
        return;
    }

    $lastMarked = (int) ($_SESSION['_last_seen_marked_at'] ?? 0);
    if ($lastMarked > time() - 60) {
        return;
    }

    db()->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = :id')->execute(['id' => $userId]);
    $_SESSION['_last_seen_marked_at'] = time();
}

function profile_is_verified(array $profile): bool
{
    $status = (string) ($profile['verification_status'] ?? $profile['overall_status'] ?? '');
    return $status === 'verified';
}

function profile_is_online(array $profile): bool
{
    $seenAt = $profile['last_seen_at'] ?? $profile['last_login_at'] ?? null;
    if (!$seenAt) {
        return false;
    }

    $timestamp = strtotime((string) $seenAt);
    return $timestamp !== false && $timestamp >= time() - 300;
}

function avatar_src(array $profile): string
{
    $src = trim((string) ($profile['avatar_url'] ?? ''));
    if ($src !== '') {
        return $src;
    }

    $seed = (string) ($profile['email'] ?? $profile['username'] ?? $profile['display_name'] ?? $profile['user_id'] ?? 'member');
    return 'https://i.pravatar.cc/150?u=' . rawurlencode($seed);
}

function profile_avatar(array $profile, string $imageClass = 'profile-avatar', array $options = []): string
{
    $size = (int) ($options['size'] ?? 0);
    $alt = (string) ($options['alt'] ?? ($profile['display_name'] ?? $profile['email'] ?? 'User avatar'));
    $wrapperClass = trim('profile-avatar-wrap ' . (string) ($options['wrapper_class'] ?? ''));
    $imageClass = trim($imageClass);
    $style = $size > 0 ? ' style="--avatar-size: ' . $size . 'px;"' : '';
    $sizeAttrs = $size > 0 ? ' width="' . $size . '" height="' . $size . '"' : '';

    $html = '<span class="' . e($wrapperClass) . '"' . $style . '>';
    $html .= '<img src="' . e(avatar_src($profile)) . '" class="' . e($imageClass) . '" alt="' . e($alt) . '"' . $sizeAttrs . '>';

    if (profile_is_verified($profile)) {
        $html .= '<span class="avatar-verified-badge" title="Verified"><i class="bi bi-patch-check-fill"></i></span>';
    }

    if (profile_is_online($profile)) {
        $html .= '<span class="avatar-online-dot" title="Online"></span>';
    }

    $html .= '</span>';
    return $html;
}
