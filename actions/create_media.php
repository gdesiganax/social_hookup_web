<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$caption = text_input('caption', 180);
$visibility = in_array(($_POST['visibility'] ?? ''), ['public', 'private'], true) ? (string) $_POST['visibility'] : 'public';

try {
    $upload = save_uploaded_media('media_file', 'profiles');
} catch (RuntimeException $e) {
    flash('danger', $e->getMessage());
    redirect('profile.php');
}

if (!$upload) {
    flash('danger', 'Choose an image or video to upload.');
    redirect('profile.php');
}

$stmt = db()->prepare(
    'INSERT INTO profile_media (user_id, media_type, media_url, caption, visibility)
     VALUES (:user_id, :media_type, :media_url, :caption, :visibility)'
);
$stmt->execute([
    'user_id' => (int) $user['id'],
    'media_type' => $upload['media_type'],
    'media_url' => $upload['media_url'],
    'caption' => $caption,
    'visibility' => $visibility,
]);

flash('success', 'Media uploaded to your profile.');
redirect('profile.php');
