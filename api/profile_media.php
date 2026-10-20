<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'public' && api_method() === 'GET') {
    api_auth_user(true);
    $userId = api_int('user_id');
    if ($userId <= 0) {
        api_error('user_id is required.');
    }
    api_json(['ok' => true, 'data' => get_profile_media($userId, false)]);
}

api_handle_resource([
    'table' => 'profile_media',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id', 'media_type', 'visibility'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'media_type', 'media_url', 'caption', 'visibility'],
    'update' => ['media_type', 'media_url', 'caption', 'visibility'],
]);
