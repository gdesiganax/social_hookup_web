<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'feed' && api_method() === 'GET') {
    api_auth_user(true);
    $limit = max(1, min(50, api_int('limit', 10)));
    $offset = max(0, api_int('offset', 0));
    api_json(['ok' => true, 'data' => get_posts($limit, $offset), 'limit' => $limit, 'offset' => $offset]);
}

if (($action === 'create' || api_method() === 'POST') && !empty($_FILES['post_media'])) {
    $user = api_auth_user(true);
    $input = api_input();
    $body = trim((string) ($input['body'] ?? $input['content'] ?? ''));
    if (mb_strlen($body) < 3) {
        api_error('Write a little more before posting.');
    }

    try {
        $upload = save_uploaded_media('post_media', 'posts', true);
    } catch (RuntimeException $e) {
        api_error($e->getMessage(), 422);
    }

    if (!$upload) {
        api_error('Choose an image to upload.');
    }

    $stmt = db()->prepare('INSERT INTO posts (user_id, body, media_type, media_url) VALUES (:user_id, :body, :media_type, :media_url)');
    $stmt->execute([
        'user_id' => (int) $user['id'],
        'body' => $body,
        'media_type' => $upload['media_type'],
        'media_url' => $upload['media_url'],
    ]);

    api_json(['ok' => true, 'data' => api_row_by_id('posts', 'id', (int) db()->lastInsertId())], 201);
}

api_handle_resource([
    'table' => 'posts',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id', 'media_type'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'body', 'media_type', 'media_url'],
    'update' => ['body', 'media_type', 'media_url'],
]);
