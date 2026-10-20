<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$displayName = text_input('display_name', 120);
$username = preg_replace('/[^a-zA-Z0-9_]/', '', text_input('username', 40));
$age = int_input('age');
$city = text_input('city', 120);
$gender = in_array(($_POST['gender'] ?? ''), ['female', 'male', 'non_binary'], true) ? (string) $_POST['gender'] : '';
$lookingFor = in_array(($_POST['looking_for'] ?? ''), ['female', 'male', 'everyone'], true) ? (string) $_POST['looking_for'] : 'everyone';
$profileVisibility = in_array(($_POST['profile_visibility'] ?? ''), ['public', 'matches', 'private'], true) ? (string) $_POST['profile_visibility'] : 'public';
$avatarUrl = text_input('avatar_url', 500);
$bio = text_input('bio', 1000);
$interestIds = array_values(array_filter(array_map('intval', (array) ($_POST['interests'] ?? []))));

if ($displayName === '' || $username === '' || $age < 18 || $age > 120 || $city === '' || $gender === '' || $bio === '') {
    flash('danger', 'Please complete your profile with valid details.');
    redirect('profile.php');
}

if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
    flash('danger', 'Avatar must be a valid URL.');
    redirect('profile.php');
}

try {
    $avatarUpload = save_uploaded_media('avatar_file', 'avatars', true);
    if ($avatarUpload) {
        $avatarUrl = $avatarUpload['media_url'];
    }
} catch (RuntimeException $e) {
    flash('danger', $e->getMessage());
    redirect('profile.php');
}

try {
    db()->beginTransaction();
    $stmt = db()->prepare(
        'UPDATE profiles
         SET display_name = :display_name, username = :username, age = :age, city = :city,
             gender = :gender, looking_for = :looking_for, profile_visibility = :profile_visibility,
             avatar_url = :avatar_url, bio = :bio, updated_at = NOW()
         WHERE user_id = :user_id'
    );
    $stmt->execute([
        'display_name' => $displayName,
        'username' => $username,
        'age' => $age,
        'city' => $city,
        'gender' => $gender,
        'looking_for' => $lookingFor,
        'profile_visibility' => $profileVisibility,
        'avatar_url' => $avatarUrl,
        'bio' => $bio,
        'user_id' => (int) $user['id'],
    ]);

    db()->prepare('DELETE FROM profile_interests WHERE user_id = :id')->execute(['id' => (int) $user['id']]);
    if ($interestIds) {
        $insert = db()->prepare('INSERT IGNORE INTO profile_interests (user_id, interest_id) VALUES (:user_id, :interest_id)');
        foreach ($interestIds as $interestId) {
            $insert->execute([
                'user_id' => (int) $user['id'],
                'interest_id' => $interestId,
            ]);
        }
    }
    db()->commit();
    flash('success', 'Profile updated.');
} catch (PDOException $e) {
    db()->rollBack();
    flash('danger', 'That username is already taken.');
}

redirect('profile.php');
