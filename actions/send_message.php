<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$conversationId = int_input('conversation_id');
$body = text_input('body', 2000);

if ($conversationId <= 0 || $body === '' || !user_can_access_conversation($conversationId, (int) $user['id'])) {
    flash('danger', 'Message could not be sent.');
    redirect('messages.php');
}

$stmt = db()->prepare(
    'INSERT INTO messages (conversation_id, sender_id, body)
     VALUES (:conversation_id, :sender_id, :body)'
);
$stmt->execute([
    'conversation_id' => $conversationId,
    'sender_id' => (int) $user['id'],
    'body' => $body,
]);

redirect('messages.php?conversation_id=' . $conversationId);
