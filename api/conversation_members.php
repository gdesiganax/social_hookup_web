<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$user = api_auth_user(true);
$method = api_method();
$conversationId = api_int('conversation_id');

if ($method === 'GET') {
    if ($conversationId <= 0 || !user_can_access_conversation($conversationId, (int) $user['id'])) {
        api_error('conversation_id is required.', 404);
    }
    api_json(['ok' => true, 'data' => get_conversation_members($conversationId)]);
}

if ($method === 'POST') {
    api_require_admin();
    $input = api_input();
    db()->prepare('INSERT IGNORE INTO conversation_members (conversation_id, user_id) VALUES (:conversation_id, :user_id)')
        ->execute(['conversation_id' => (int) ($input['conversation_id'] ?? 0), 'user_id' => (int) ($input['user_id'] ?? 0)]);
    api_json(['ok' => true], 201);
}

if ($method === 'DELETE') {
    api_require_admin();
    $memberUserId = api_int('user_id');
    if ($conversationId <= 0 || $memberUserId <= 0) {
        api_error('conversation_id and user_id are required.');
    }
    db()->prepare('DELETE FROM conversation_members WHERE conversation_id = :conversation_id AND user_id = :user_id')
        ->execute(['conversation_id' => $conversationId, 'user_id' => $memberUserId]);
    api_json(['ok' => true, 'deleted' => true]);
}

api_error('Method not allowed.', 405);

