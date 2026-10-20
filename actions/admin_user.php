<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$admin = require_admin();
require_post();

$userId = int_input('user_id');
$action = text_input('action', 20);

if ($userId <= 0 || $userId === (int) $admin['id']) {
    flash('danger', 'You cannot change that account.');
    redirect('admin/users.php');
}

if ($action === 'activate') {
    db()->prepare('UPDATE users SET status = "active", updated_at = NOW() WHERE id = :id')->execute(['id' => $userId]);
    flash('success', 'User activated.');
} elseif ($action === 'suspend') {
    db()->prepare('UPDATE users SET status = "suspended", updated_at = NOW() WHERE id = :id')->execute(['id' => $userId]);
    flash('success', 'User suspended.');
} elseif ($action === 'delete') {
    db()->prepare('UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id')->execute(['id' => $userId]);
    flash('success', 'User deleted.');
} elseif ($action === 'verify') {
    db()->prepare(
        'UPDATE user_verifications
         SET phone_status = "verified", address_status = "verified", government_id_status = "verified",
             overall_status = "verified", rejection_reason = NULL, reviewed_by = :admin_id, reviewed_at = NOW()
         WHERE user_id = :id'
    )->execute([
        'admin_id' => (int) $admin['id'],
        'id' => $userId,
    ]);
    flash('success', 'User verification approved.');
} elseif ($action === 'reject_verification') {
    $reason = text_input('rejection_reason', 255) ?: 'Verification details could not be approved.';
    db()->prepare(
        'UPDATE user_verifications
         SET phone_status = "rejected", address_status = "rejected", government_id_status = "rejected",
             overall_status = "rejected", rejection_reason = :reason, reviewed_by = :admin_id, reviewed_at = NOW()
         WHERE user_id = :id'
    )->execute([
        'reason' => $reason,
        'admin_id' => (int) $admin['id'],
        'id' => $userId,
    ]);
    flash('success', 'User verification rejected.');
} else {
    flash('danger', 'Invalid admin action.');
}

redirect('admin/users.php');
