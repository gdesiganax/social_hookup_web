<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'conversation' && api_method() === 'GET') {
    $user = api_auth_user(true);
    $conversationId = api_int('conversation_id');
    if ($conversationId <= 0 || !user_can_access_conversation($conversationId, (int) $user['id'])) {
        api_error('Conversation not found.', 404);
    }
    api_json(['ok' => true, 'data' => get_conversation_messages($conversationId, (int) $user['id'])]);
}

if (api_method() === 'POST') {
    $user = api_auth_user(true);
    $input = api_input();
    $conversationId = (int) ($input['conversation_id'] ?? 0);
    $body = trim((string) ($input['body'] ?? ''));
    if ($conversationId <= 0 || !user_can_access_conversation($conversationId, (int) $user['id'])) {
        api_error('Conversation not found.', 404);
    }
    if ($body === '') {
        api_error('Message body is required.');
    }
    db()->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (:conversation_id, :sender_id, :body)')
        ->execute(['conversation_id' => $conversationId, 'sender_id' => (int) $user['id'], 'body' => mb_substr($body, 0, 2000)]);
    api_json(['ok' => true, 'id' => (int) db()->lastInsertId()], 201);
}

api_handle_resource([
    'table' => 'messages',
    'primary_key' => 'id',
    'filters' => ['id', 'conversation_id', 'sender_id'],
    'owner_column' => 'sender_id',
    'create' => ['conversation_id', 'sender_id', 'body'],
    'update' => ['body'],
]);
