<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'discovery' && api_method() === 'GET') {
    $gender = in_array(($_GET['gender'] ?? $_GET['meet'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) ($_GET['gender'] ?? $_GET['meet']) : '';
    $city = trim((string) ($_GET['city'] ?? ''));
    $limit = max(1, min(24, api_int('limit', 12)));
    api_json(['ok' => true, 'data' => get_discovery_profiles($gender, $city, $limit)]);
}

if ($action === 'public' && api_method() === 'GET') {
    $user = api_auth_user(true);
    $search = trim((string) ($_GET['search'] ?? ''));
    $gender = in_array(($_GET['gender'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) $_GET['gender'] : '';
    $city = trim((string) ($_GET['city'] ?? ''));
    api_json(['ok' => true, 'data' => get_public_profiles((int) $user['id'], api_int('limit', 12), $search, $gender, $city)]);
}

if ($action === 'me' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => get_profile((int) $user['id'])]);
}

api_handle_resource([
    'table' => 'profiles',
    'primary_key' => 'user_id',
    'public_get' => true,
    'filters' => ['user_id', 'username', 'city', 'gender', 'looking_for', 'is_featured'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'display_name', 'username', 'age', 'city', 'gender', 'looking_for', 'profile_visibility', 'bio', 'avatar_url', 'is_featured'],
    'update' => ['display_name', 'username', 'age', 'city', 'gender', 'looking_for', 'profile_visibility', 'bio', 'avatar_url'],
]);

