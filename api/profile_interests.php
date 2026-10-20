<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$user = api_auth_user(true);
$method = api_method();
$profileUserId = api_int('user_id', (int) $user['id']);

if ($method === 'GET') {
    api_json(['ok' => true, 'data' => get_profile_interests($profileUserId)]);
}

if ($method === 'POST') {
    $input = api_input();
    $targetUserId = api_is_admin($user) ? (int) ($input['user_id'] ?? $user['id']) : (int) $user['id'];
    $interestId = (int) ($input['interest_id'] ?? 0);
    if ($interestId <= 0) {
        api_error('interest_id is required.');
    }
    db()->prepare('INSERT IGNORE INTO profile_interests (user_id, interest_id) VALUES (:user_id, :interest_id)')
        ->execute(['user_id' => $targetUserId, 'interest_id' => $interestId]);
    api_json(['ok' => true], 201);
}

if ($method === 'DELETE') {
    $targetUserId = api_is_admin($user) ? api_int('user_id', (int) $user['id']) : (int) $user['id'];
    $interestId = api_int('interest_id');
    if ($interestId <= 0) {
        api_error('interest_id is required.');
    }
    db()->prepare('DELETE FROM profile_interests WHERE user_id = :user_id AND interest_id = :interest_id')
        ->execute(['user_id' => $targetUserId, 'interest_id' => $interestId]);
    api_json(['ok' => true, 'deleted' => true]);
}

api_error('Method not allowed.', 405);

