<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'mine' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => get_matched_profiles((int) $user['id'], trim((string) ($_GET['search'] ?? '')))]);
}

if ($action === 'match' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $otherUserId = (int) (api_input()['user_id'] ?? 0);
    if ($otherUserId <= 0 || $otherUserId === (int) $user['id']) {
        api_error('A valid user_id is required.');
    }
    $first = min((int) $user['id'], $otherUserId);
    $second = max((int) $user['id'], $otherUserId);
    db()->prepare(
        'INSERT INTO matches (user_one_id, user_two_id, status, matched_at)
         VALUES (:one, :two, "matched", NOW())
         ON DUPLICATE KEY UPDATE status = "matched", matched_at = NOW(), updated_at = NOW()'
    )->execute(['one' => $first, 'two' => $second]);
    api_json(['ok' => true, 'matched' => true], 201);
}

api_handle_resource([
    'table' => 'matches',
    'primary_key' => 'id',
    'admin_only' => true,
    'filters' => ['id', 'user_one_id', 'user_two_id', 'status'],
    'create' => ['user_one_id', 'user_two_id', 'status', 'matched_at'],
    'update' => ['status', 'matched_at'],
]);
