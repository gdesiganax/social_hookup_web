<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$phone = preg_replace('/[^0-9+]/', '', text_input('phone_number', 40));
$address = text_input('address_line', 255);
$city = text_input('city', 120);
$state = text_input('state', 120);
$nin = preg_replace('/\D/', '', text_input('nin_number', 20));

if ($phone === '' || strlen($phone) < 8 || $address === '' || $city === '' || $state === '' || strlen($nin) !== 11) {
    flash('danger', 'Complete your phone, address, and 11-digit Nigeria NIN.');
    redirect('profile.php');
}

$existing = get_user_verification((int) $user['id']);
if ($existing && $existing['overall_status'] === 'verified') {
    flash('warning', 'Your account is already verified.');
    redirect('profile.php');
}

try {
    $document = save_uploaded_verification_document('nin_document');
} catch (RuntimeException $e) {
    flash('danger', $e->getMessage());
    redirect('profile.php');
}

if (!$document) {
    flash('danger', 'Upload your Nigeria NIN document.');
    redirect('profile.php');
}

$stmt = db()->prepare(
    'INSERT INTO user_verifications (
        user_id, phone_number, address_line, city, state, nin_number,
        government_id_type, government_id_path, government_id_original_name, government_id_mime,
        phone_status, address_status, government_id_status, overall_status,
        rejection_reason, submitted_at, reviewed_by, reviewed_at
     ) VALUES (
        :user_id, :phone_number, :address_line, :city, :state, :nin_number,
        "nin", :government_id_path, :government_id_original_name, :government_id_mime,
        "pending", "pending", "pending", "pending",
        NULL, NOW(), NULL, NULL
     )
     ON DUPLICATE KEY UPDATE
        phone_number = VALUES(phone_number),
        address_line = VALUES(address_line),
        city = VALUES(city),
        state = VALUES(state),
        nin_number = VALUES(nin_number),
        government_id_path = VALUES(government_id_path),
        government_id_original_name = VALUES(government_id_original_name),
        government_id_mime = VALUES(government_id_mime),
        phone_status = "pending",
        address_status = "pending",
        government_id_status = "pending",
        overall_status = "pending",
        rejection_reason = NULL,
        submitted_at = NOW(),
        reviewed_by = NULL,
        reviewed_at = NULL'
);
$stmt->execute([
    'user_id' => (int) $user['id'],
    'phone_number' => $phone,
    'address_line' => $address,
    'city' => $city,
    'state' => $state,
    'nin_number' => $nin,
    'government_id_path' => $document['path'],
    'government_id_original_name' => $document['original_name'],
    'government_id_mime' => $document['mime_type'],
]);

flash('success', 'Verification submitted. An admin will review your NIN document.');
redirect('profile.php');
