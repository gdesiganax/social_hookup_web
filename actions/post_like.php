<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$postId = int_input('post_id');

if ($postId > 0) {
    $stmt = db()->prepare(
        'INSERT IGNORE INTO post_reactions (post_id, user_id, reaction_type)
         VALUES (:post_id, :user_id, "like")'
    );
    $stmt->execute([
        'post_id' => $postId,
        'user_id' => (int) $user['id'],
    ]);
    flash('success', 'Liked.');
}

redirect('dashboard.php');
