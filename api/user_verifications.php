<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

$action = (string) ($_GET['action'] ?? api_input()['action'] ?? '');

if ($action === 'review' && api_method() === 'PATCH') {
    $admin = api_require_admin();
    $input = api_input();
    $userId = (int) ($input['user_id'] ?? 0);
    $status = in_array(($input['overall_status'] ?? ''), ['pending', 'verified', 'rejected'], true) ? (string) $input['overall_status'] : '';
    if ($userId <= 0 || $status === '') {
        api_error('user_id and overall_status are required.');
    }
    db()->prepare(
        'UPDATE user_verifications
         SET phone_status = :status, address_status = :status, government_id_status = :status,
             overall_status = :status, rejection_reason = :reason, reviewed_by = :admin_id, reviewed_at = NOW()
         WHERE user_id = :user_id'
    )->execute([
        'status' => $status,
        'reason' => $status === 'rejected' ? trim((string) ($input['rejection_reason'] ?? '')) : null,
        'admin_id' => (int) $admin['id'],
        'user_id' => $userId,
    ]);
    api_json(['ok' => true]);
}

api_handle_resource([
    'table' => 'user_verifications',
    'primary_key' => 'id',
    'filters' => ['id', 'user_id', 'overall_status'],
    'owner_column' => 'user_id',
    'create' => ['user_id', 'phone_number', 'address_line', 'city', 'state', 'nin_number', 'government_id_type', 'government_id_path', 'government_id_original_name', 'government_id_mime'],
    'update' => ['phone_number', 'address_line', 'city', 'state', 'nin_number', 'government_id_type', 'government_id_path', 'government_id_original_name', 'government_id_mime'],
]);
