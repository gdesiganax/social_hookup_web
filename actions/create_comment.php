<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$postId = int_input('post_id');
$body = text_input('body', 500);

if ($postId <= 0 || mb_strlen($body) < 1) {
    flash('danger', 'Write a comment before submitting.');
    redirect('dashboard.php');
}

$postStmt = db()->prepare('SELECT id FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
$postStmt->execute(['id' => $postId]);
if (!$postStmt->fetch()) {
    flash('danger', 'That post is no longer available.');
    redirect('dashboard.php');
}

$stmt = db()->prepare('INSERT INTO post_comments (post_id, user_id, body) VALUES (:post_id, :user_id, :body)');
$stmt->execute([
    'post_id' => $postId,
    'user_id' => (int) $user['id'],
    'body' => $body,
]);

flash('success', 'Comment posted.');
redirect('dashboard.php');
