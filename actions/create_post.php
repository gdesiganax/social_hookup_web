<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$body = text_input('body', 1000);
$upload = null;

if ($body === '' || mb_strlen($body) < 3) {
    flash('danger', 'Write a little more before posting.');
    redirect('dashboard.php');
}

try {
    $upload = save_uploaded_media('post_media', 'posts');
} catch (RuntimeException $e) {
    flash('danger', $e->getMessage());
    redirect('dashboard.php');
}

$stmt = db()->prepare('INSERT INTO posts (user_id, body, media_type, media_url) VALUES (:user_id, :body, :media_type, :media_url)');
$stmt->execute([
    'user_id' => (int) $user['id'],
    'body' => $body,
    'media_type' => $upload['media_type'] ?? null,
    'media_url' => $upload['media_url'] ?? null,
]);

flash('success', 'Your post is live.');
redirect('dashboard.php');
