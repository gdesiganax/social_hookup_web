<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'mine' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => get_conversations((int) $user['id'], trim((string) ($_GET['search'] ?? '')))]);
}

if ($action === 'direct' && api_method() === 'POST') {
    $user = api_auth_user(true);
    $otherUserId = (int) (api_input()['user_id'] ?? 0);
    if ($otherUserId <= 0 || $otherUserId === (int) $user['id']) {
        api_error('A valid user_id is required.');
    }
    $conversationId = find_or_create_direct_conversation((int) $user['id'], $otherUserId);
    api_json(['ok' => true, 'conversation_id' => $conversationId], 201);
}

api_handle_resource([
    'table' => 'conversations',
    'primary_key' => 'id',
    'filters' => ['id', 'type', 'created_by'],
    'owner_column' => 'created_by',
    'create' => ['type', 'title', 'created_by'],
    'update' => ['title'],
]);

