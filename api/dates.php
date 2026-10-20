<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'upcoming' && api_method() === 'GET') {
    $user = api_auth_user(true);
    api_json(['ok' => true, 'data' => get_upcoming_dates((int) $user['id'])]);
}

api_handle_resource([
    'table' => 'dates',
    'primary_key' => 'id',
    'admin_only' => true,
    'filters' => ['id', 'user_one_id', 'user_two_id', 'status'],
    'create' => ['user_one_id', 'user_two_id', 'scheduled_at', 'location', 'status'],
    'update' => ['scheduled_at', 'location', 'status'],
]);
