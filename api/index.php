<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

api_json([
    'ok' => true,
    'name' => 'HookUp REST API',
    'auth' => [
        'register' => 'POST /api/users.php?action=register',
        'login' => 'POST /api/users.php?action=login',
        'me' => 'GET /api/users.php?action=me',
    ],
    'tables' => [
        'users',
        'profiles',
        'profile_media',
        'user_verifications',
        'interests',
        'profile_interests',
        'matches',
        'conversations',
        'conversation_members',
        'messages',
        'posts',
        'post_reactions',
        'post_comments',
        'wallet_accounts',
        'gift_catalog',
        'gift_purchases',
        'wallet_transactions',
        'wallet_topups',
        'withdrawal_requests',
        'dates',
        'reports',
        'settings',
    ],
]);

