<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$otherUserId = int_input('user_id');

if ($otherUserId <= 0 || $otherUserId === (int) $user['id']) {
    flash('danger', 'Invalid match target.');
    redirect('matches.php');
}

$first = min((int) $user['id'], $otherUserId);
$second = max((int) $user['id'], $otherUserId);

$stmt = db()->prepare(
    'INSERT INTO matches (user_one_id, user_two_id, status, matched_at)
     VALUES (:one, :two, "matched", NOW())
     ON DUPLICATE KEY UPDATE status = "matched", matched_at = NOW(), updated_at = NOW()'
);
$stmt->execute([
    'one' => $first,
    'two' => $second,
]);

flash('success', 'Match saved.');
redirect('matches.php');
