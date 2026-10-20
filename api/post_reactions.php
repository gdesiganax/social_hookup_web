<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$user = api_auth_user(true);
$method = api_method();
$postId = api_int('post_id');

if ($method === 'GET') {
    $where = $postId > 0 ? 'WHERE post_id = :post_id' : 'WHERE user_id = :user_id';
    $params = $postId > 0 ? ['post_id' => $postId] : ['user_id' => (int) $user['id']];
    $stmt = db()->prepare('SELECT * FROM post_reactions ' . $where . ' ORDER BY created_at DESC LIMIT 100');
    $stmt->execute($params);
    api_json(['ok' => true, 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    if ($postId <= 0) {
        $postId = (int) (api_input()['post_id'] ?? 0);
    }
    if ($postId <= 0) {
        api_error('post_id is required.');
    }
    db()->prepare('INSERT IGNORE INTO post_reactions (post_id, user_id, reaction_type) VALUES (:post_id, :user_id, "like")')
        ->execute(['post_id' => $postId, 'user_id' => (int) $user['id']]);
    api_json(['ok' => true, 'liked' => true], 201);
}

if ($method === 'DELETE') {
    if ($postId <= 0) {
        api_error('post_id is required.');
    }
    db()->prepare('DELETE FROM post_reactions WHERE post_id = :post_id AND user_id = :user_id')
        ->execute(['post_id' => $postId, 'user_id' => (int) $user['id']]);
    api_json(['ok' => true, 'deleted' => true]);
}

api_error('Method not allowed.', 405);

