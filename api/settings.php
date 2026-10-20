<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$user = api_require_admin();
$method = api_method();
$key = trim((string) ($_GET['setting_key'] ?? $_GET['key'] ?? api_input()['setting_key'] ?? ''));

if ($method === 'GET') {
    if ($key !== '') {
        api_json(['ok' => true, 'data' => ['setting_key' => $key, 'setting_value' => get_setting($key)]]);
    }
    $stmt = db()->query('SELECT setting_key, setting_value, updated_at FROM settings ORDER BY setting_key');
    api_json(['ok' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $input = api_input();
    $key = trim((string) ($input['setting_key'] ?? $input['key'] ?? $key));
    if ($key === '') {
        api_error('setting_key is required.');
    }
    set_setting($key, (string) ($input['setting_value'] ?? $input['value'] ?? ''));
    api_json(['ok' => true, 'data' => ['setting_key' => $key, 'setting_value' => get_setting($key)]], $method === 'POST' ? 201 : 200);
}

if ($method === 'DELETE') {
    if ($key === '') {
        api_error('setting_key is required.');
    }
    db()->prepare('DELETE FROM settings WHERE setting_key = :key')->execute(['key' => $key]);
    api_json(['ok' => true, 'deleted' => true]);
}

api_error('Method not allowed.', 405);
