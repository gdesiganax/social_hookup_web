<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'for_post' && api_method() === 'GET') {
    api_auth_user(true);
    $postId = api_int('post_id');
    if ($postId <= 0) {
        api_error('post_id is required.');
    }
    api_json(['ok' => true, 'data' => get_post_comments($postId, api_int('limit', 25))]);
}

api_handle_resource([
    'table' => 'post_comments',
    'primary_key' => 'id',
    'filters' => ['id', 'post_id', 'user_id'],
    'owner_column' => 'user_id',
    'create' => ['post_id', 'user_id', 'body'],
    'update' => ['body'],
]);

