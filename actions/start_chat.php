<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
require_post();

$otherUserId = int_input('user_id');

if ($otherUserId <= 0 || $otherUserId === (int) $user['id']) {
    flash('danger', 'Invalid conversation target.');
    redirect('matches.php');
}

$profile = get_profile($otherUserId);
if (!$profile || $profile['status'] !== 'active') {
    flash('danger', 'That member is unavailable.');
    redirect('matches.php');
}

$conversationId = find_or_create_direct_conversation((int) $user['id'], $otherUserId);
redirect('messages.php?conversation_id=' . $conversationId);
