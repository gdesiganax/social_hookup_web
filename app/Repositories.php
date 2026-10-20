<?php

declare(strict_types=1);

function get_setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );
    $stmt->execute(['k' => $key, 'v' => $value]);
}

function get_public_profiles(int $viewerId, int $limit = 12, string $search = '', string $gender = '', string $city = ''): array
{
    $viewer = get_profile($viewerId);
    $preferredGender = $gender ?: (string) ($viewer['looking_for'] ?? '');
    $preferredCity = $city ?: (string) ($viewer['city'] ?? '');
    $presenceSelect = user_presence_select('u');

    $sql = 'SELECT u.id AS user_id, u.email, u.status, p.*,
            uv.overall_status AS verification_status, ' . $presenceSelect . ',
            (SELECT COUNT(*) FROM matches m WHERE (m.user_one_id = u.id OR m.user_two_id = u.id) AND m.status = "matched") AS match_count
            FROM users u
            JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE u.id <> :viewer AND u.deleted_at IS NULL AND u.status = "active"';
    $params = ['viewer' => $viewerId];

    if ($preferredGender !== '' && $preferredGender !== 'everyone') {
        $sql .= ' AND p.gender = :gender';
        $params['gender'] = $preferredGender;
    }

    if ($search !== '') {
        $sql .= ' AND (p.display_name LIKE :search OR p.username LIKE :search OR p.city LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($preferredCity !== '') {
        $sql .= ' ORDER BY CASE WHEN p.city = :city_order THEN 0 ELSE 1 END, p.is_featured DESC, u.created_at DESC';
        $params['city_order'] = $preferredCity;
    } else {
        $sql .= ' ORDER BY p.is_featured DESC, u.created_at DESC';
    }
    $sql .= ' LIMIT ' . max(1, min(50, $limit));
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_discovery_profiles(string $gender = '', string $city = '', int $limit = 12): array
{
    $presenceSelect = user_presence_select('u');
    $sql = 'SELECT u.id AS user_id, u.email, u.status, p.*,
                   uv.overall_status AS verification_status, ' . $presenceSelect . '
            FROM users u
            JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE u.deleted_at IS NULL AND u.status = "active" AND u.role = "member"';
    $params = [];

    if ($gender !== '' && $gender !== 'everyone') {
        $sql .= ' AND p.gender = :gender';
        $params['gender'] = $gender;
    }

    if ($city !== '') {
        $sql .= ' ORDER BY CASE WHEN p.city LIKE :city_order THEN 0 ELSE 1 END, p.is_featured DESC, RAND()';
        $params['city_order'] = '%' . $city . '%';
    } else {
        $sql .= ' ORDER BY p.is_featured DESC, RAND()';
    }

    $sql .= ' LIMIT ' . max(1, min(24, $limit));
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_matched_profiles(int $userId, string $search = ''): array
{
    $presenceSelect = user_presence_select('u');
    $sql = 'SELECT u.id AS user_id, u.email, u.status, p.*, m.matched_at,
                   uv.overall_status AS verification_status, ' . $presenceSelect . '
            FROM matches m
            JOIN users u ON u.id = CASE WHEN m.user_one_id = :case_id THEN m.user_two_id ELSE m.user_one_id END
            JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE (m.user_one_id = :one_id OR m.user_two_id = :two_id)
              AND m.status = "matched"
              AND u.deleted_at IS NULL';
    $params = [
        'case_id' => $userId,
        'one_id' => $userId,
        'two_id' => $userId,
    ];

    if ($search !== '') {
        $sql .= ' AND (p.display_name LIKE :search OR p.username LIKE :search OR p.city LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY m.matched_at DESC, p.display_name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_profile(int $userId): ?array
{
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT u.id AS user_id, u.email, u.role, u.status, u.created_at, ' . $presenceSelect . ',
                p.*, uv.overall_status AS verification_status
         FROM users u JOIN profiles p ON p.user_id = u.id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE u.id = :id AND u.deleted_at IS NULL LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $profile = $stmt->fetch();
    return $profile ?: null;
}

function users_are_matched(int $userId, int $otherUserId): bool
{
    $first = min($userId, $otherUserId);
    $second = max($userId, $otherUserId);
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM matches
         WHERE user_one_id = :one AND user_two_id = :two AND status = "matched"'
    );
    $stmt->execute(['one' => $first, 'two' => $second]);
    return (int) $stmt->fetchColumn() > 0;
}

function can_view_profile_page(array $viewer, array $profile): bool
{
    if ((int) $viewer['id'] === (int) $profile['user_id']) {
        return true;
    }

    $visibility = $profile['profile_visibility'] ?? 'public';
    if ($visibility === 'public') {
        return true;
    }
    if ($visibility === 'matches') {
        return users_are_matched((int) $viewer['id'], (int) $profile['user_id']);
    }
    return false;
}

function get_profile_interests(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT i.name FROM interests i
         JOIN profile_interests pi ON pi.interest_id = i.id
         WHERE pi.user_id = :id
         ORDER BY i.name'
    );
    $stmt->execute(['id' => $userId]);
    return array_column($stmt->fetchAll(), 'name');
}

function get_all_interests(): array
{
    return db()->query('SELECT id, name FROM interests ORDER BY name')->fetchAll();
}

function get_profile_media(int $profileUserId, bool $includePrivate = false): array
{
    $sql = 'SELECT * FROM profile_media WHERE user_id = :user_id';
    $params = ['user_id' => $profileUserId];
    if (!$includePrivate) {
        $sql .= ' AND visibility = "public"';
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_user_verification(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM user_verifications WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $verification = $stmt->fetch();
    return $verification ?: null;
}

function user_is_verified(int $userId): bool
{
    $verification = get_user_verification($userId);
    return $verification && $verification['overall_status'] === 'verified';
}

function get_posts(int $limit = 10, int $offset = 0): array
{
    $limit = max(1, min(50, $limit));
    $offset = max(0, $offset);
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT posts.*, p.display_name, p.username, p.city, p.avatar_url,
            uv.overall_status AS verification_status, ' . $presenceSelect . ',
            (SELECT COUNT(*) FROM post_reactions r WHERE r.post_id = posts.id) AS likes_count,
            (SELECT COUNT(*) FROM post_comments c WHERE c.post_id = posts.id) AS comments_count
         FROM posts
         JOIN profiles p ON p.user_id = posts.user_id
         JOIN users u ON u.id = posts.user_id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE posts.deleted_at IS NULL AND u.deleted_at IS NULL
         ORDER BY posts.created_at DESC
         LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_post_comments(int $postId, int $limit = 5): array
{
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT c.*, p.display_name, p.username, p.avatar_url,
                uv.overall_status AS verification_status, ' . $presenceSelect . '
         FROM post_comments c
         JOIN profiles p ON p.user_id = c.user_id
         JOIN users u ON u.id = c.user_id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE c.post_id = :post_id AND c.deleted_at IS NULL AND u.deleted_at IS NULL
         ORDER BY c.created_at ASC, c.id ASC
         LIMIT ' . max(1, min(25, $limit))
    );
    $stmt->execute(['post_id' => $postId]);
    return $stmt->fetchAll();
}

function get_upcoming_dates(int $userId): array
{
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT d.*, p.display_name, p.avatar_url,
                uv.overall_status AS verification_status, ' . $presenceSelect . '
         FROM dates d
         JOIN users u ON u.id = CASE WHEN d.user_one_id = :case_id THEN d.user_two_id ELSE d.user_one_id END
         JOIN profiles p ON p.user_id = u.id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE (d.user_one_id = :one_id OR d.user_two_id = :two_id) AND d.scheduled_at >= NOW()
         ORDER BY d.scheduled_at ASC
         LIMIT 5'
    );
    $stmt->execute([
        'case_id' => $userId,
        'one_id' => $userId,
        'two_id' => $userId,
    ]);
    return $stmt->fetchAll();
}

function get_conversations(int $userId, string $search = ''): array
{
    $sql = 'SELECT c.id, c.type, c.title, c.created_by, c.created_at, c.updated_at,
            lm.created_at AS last_message_at,
            lm.body AS last_message,
            COALESCE(
                NULLIF(GROUP_CONCAT(DISTINCT CASE WHEN p.user_id <> :label_user THEN p.display_name END ORDER BY p.display_name SEPARATOR ", "), ""),
                GROUP_CONCAT(DISTINCT p.display_name ORDER BY p.display_name SEPARATOR ", ")
            ) AS member_names
        FROM conversations c
        JOIN conversation_members cm ON cm.conversation_id = c.id
        JOIN conversation_members cm_all ON cm_all.conversation_id = c.id
        JOIN profiles p ON p.user_id = cm_all.user_id
        LEFT JOIN messages lm ON lm.id = (
            SELECT m2.id FROM messages m2
            WHERE m2.conversation_id = c.id AND m2.deleted_at IS NULL
            ORDER BY m2.created_at DESC, m2.id DESC
            LIMIT 1
        )
        WHERE cm.user_id = :id
          AND lm.id IS NOT NULL
          AND c.type = "direct"';
    $params = [
        'id' => $userId,
        'label_user' => $userId,
    ];
    if ($search !== '') {
        $sql .= ' AND (c.title LIKE :search OR p.display_name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    $sql .= ' GROUP BY c.id, c.type, c.title, c.created_by, c.created_at, c.updated_at, lm.created_at, lm.body
              ORDER BY COALESCE(last_message_at, c.created_at) DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function user_can_access_conversation(int $conversationId, int $userId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM conversation_members WHERE conversation_id = :cid AND user_id = :uid');
    $stmt->execute(['cid' => $conversationId, 'uid' => $userId]);
    return (int) $stmt->fetchColumn() > 0;
}

function get_conversation_messages(int $conversationId, int $userId): array
{
    if (!user_can_access_conversation($conversationId, $userId)) {
        return [];
    }
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT m.*, p.display_name, p.avatar_url,
                uv.overall_status AS verification_status, ' . $presenceSelect . '
         FROM messages m
         JOIN profiles p ON p.user_id = m.sender_id
         JOIN users u ON u.id = m.sender_id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE m.conversation_id = :cid
         ORDER BY m.created_at ASC
         LIMIT 200'
    );
    $stmt->execute(['cid' => $conversationId]);
    return $stmt->fetchAll();
}

function get_conversation_members(int $conversationId): array
{
    $presenceSelect = user_presence_select('u');
    $stmt = db()->prepare(
        'SELECT p.user_id, p.display_name, p.avatar_url,
                uv.overall_status AS verification_status, ' . $presenceSelect . '
         FROM conversation_members cm
         JOIN profiles p ON p.user_id = cm.user_id
         JOIN users u ON u.id = cm.user_id
         LEFT JOIN user_verifications uv ON uv.user_id = u.id
         WHERE cm.conversation_id = :cid
         ORDER BY p.display_name'
    );
    $stmt->execute(['cid' => $conversationId]);
    return $stmt->fetchAll();
}

function find_or_create_direct_conversation(int $userId, int $otherUserId): int
{
    $stmt = db()->prepare(
        'SELECT c.id FROM conversations c
         JOIN conversation_members a ON a.conversation_id = c.id AND a.user_id = :u1
         JOIN conversation_members b ON b.conversation_id = c.id AND b.user_id = :u2
         WHERE c.type = "direct"
         LIMIT 1'
    );
    $stmt->execute(['u1' => $userId, 'u2' => $otherUserId]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int) $existing;
    }

    db()->beginTransaction();
    try {
        db()->prepare('INSERT INTO conversations (type, created_by) VALUES ("direct", :uid)')->execute(['uid' => $userId]);
        $conversationId = (int) db()->lastInsertId();
        $member = db()->prepare('INSERT INTO conversation_members (conversation_id, user_id) VALUES (:cid, :uid)');
        $member->execute(['cid' => $conversationId, 'uid' => $userId]);
        $member->execute(['cid' => $conversationId, 'uid' => $otherUserId]);
        db()->commit();
        return $conversationId;
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function ensure_wallet_account(int $userId): array
{
    db()->prepare('INSERT IGNORE INTO wallet_accounts (user_id) VALUES (:user_id)')->execute(['user_id' => $userId]);
    $stmt = db()->prepare('SELECT * FROM wallet_accounts WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch() ?: [];
}

function get_gift_catalog(): array
{
    return db()->query('SELECT * FROM gift_catalog WHERE is_active = 1 ORDER BY price ASC, name ASC')->fetchAll();
}

function get_conversation_gifts(int $conversationId, int $userId): array
{
    if (!user_can_access_conversation($conversationId, $userId)) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT gp.*, gc.name AS gift_name, gc.icon AS gift_icon,
                sender.display_name AS sender_name, receiver.display_name AS recipient_name
         FROM gift_purchases gp
         JOIN gift_catalog gc ON gc.id = gp.gift_id
         JOIN profiles sender ON sender.user_id = gp.sender_id
         JOIN profiles receiver ON receiver.user_id = gp.recipient_id
         WHERE gp.conversation_id = :conversation_id
         ORDER BY gp.created_at DESC
         LIMIT 20'
    );
    $stmt->execute(['conversation_id' => $conversationId]);
    return $stmt->fetchAll();
}

function get_wallet_transactions(int $userId, int $limit = 8): array
{
    $wallet = ensure_wallet_account($userId);
    $stmt = db()->prepare(
        'SELECT * FROM wallet_transactions
         WHERE wallet_account_id = :wallet_account_id
         ORDER BY created_at DESC, id DESC
         LIMIT ' . max(1, min(25, $limit))
    );
    $stmt->execute(['wallet_account_id' => (int) $wallet['id']]);
    return $stmt->fetchAll();
}

function get_wallet_topups(int $userId, int $limit = 5): array
{
    $stmt = db()->prepare(
        'SELECT * FROM wallet_topups
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT ' . max(1, min(20, $limit))
    );
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function admin_stats(): array
{
    return [
        'users' => table_count('users', 'deleted_at IS NULL'),
        'active' => table_count('users', 'status = "active" AND deleted_at IS NULL'),
        'matches' => table_count('matches', 'status = "matched"'),
        'reports' => table_count('reports', 'status = "open"'),
        'pending_payments' => table_count('withdrawal_requests', 'status = "pending"'),
    ];
}

function admin_users(string $search = '', string $status = ''): array
{
    $presenceSelect = user_presence_select('u');
    $sql = 'SELECT u.id, u.email, u.role, u.status, u.created_at, p.display_name, p.username, p.avatar_url,
            uv.id AS verification_id, uv.phone_number, uv.address_line, uv.city AS verification_city,
            uv.state AS verification_state, uv.nin_number, uv.government_id_original_name,
            uv.overall_status AS verification_status, uv.submitted_at AS verification_submitted_at,
            ' . $presenceSelect . ',
            (SELECT COUNT(*) FROM matches m WHERE (m.user_one_id = u.id OR m.user_two_id = u.id) AND m.status = "matched") AS match_count
            FROM users u LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE u.deleted_at IS NULL';
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (u.email LIKE :search OR p.display_name LIKE :search OR p.username LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    if ($status !== '') {
        $sql .= ' AND u.status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY u.created_at DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function admin_wallet_transactions(string $search = '', string $type = '', string $balanceType = ''): array
{
    $allowedTypes = ['seed_credit', 'wallet_funding', 'gift_sent', 'gift_received', 'gift_conversion', 'withdrawal_request'];
    $allowedBalances = ['cash', 'gift', 'pending_withdrawal'];
    $presenceSelect = user_presence_select('u');

    $sql = 'SELECT wt.*, u.id AS user_id, u.email, p.display_name, p.username, p.avatar_url,
                   uv.overall_status AS verification_status, ' . $presenceSelect . ',
                   related.email AS related_email, related_profile.display_name AS related_display_name
            FROM wallet_transactions wt
            JOIN wallet_accounts wa ON wa.id = wt.wallet_account_id
            JOIN users u ON u.id = wa.user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            LEFT JOIN users related ON related.id = wt.related_user_id
            LEFT JOIN profiles related_profile ON related_profile.user_id = related.id
            WHERE u.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (u.email LIKE :search_email OR p.display_name LIKE :search_name OR p.username LIKE :search_username OR wt.description LIKE :search_description OR related.email LIKE :search_related_email OR related_profile.display_name LIKE :search_related_name)';
        $needle = '%' . $search . '%';
        $params['search_email'] = $needle;
        $params['search_name'] = $needle;
        $params['search_username'] = $needle;
        $params['search_description'] = $needle;
        $params['search_related_email'] = $needle;
        $params['search_related_name'] = $needle;
    }
    if (in_array($type, $allowedTypes, true)) {
        $sql .= ' AND wt.transaction_type = :type';
        $params['type'] = $type;
    }
    if (in_array($balanceType, $allowedBalances, true)) {
        $sql .= ' AND wt.balance_type = :balance_type';
        $params['balance_type'] = $balanceType;
    }

    $sql .= ' ORDER BY wt.created_at DESC, wt.id DESC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function admin_wallet_topups(string $search = '', string $status = ''): array
{
    $allowedStatuses = ['pending', 'success', 'failed'];
    $presenceSelect = user_presence_select('u');

    $sql = 'SELECT topups.*, u.id AS user_id, u.email, p.display_name, p.username, p.avatar_url,
                   uv.overall_status AS verification_status, ' . $presenceSelect . '
            FROM wallet_topups topups
            JOIN users u ON u.id = topups.user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE u.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (u.email LIKE :search_email OR p.display_name LIKE :search_name OR p.username LIKE :search_username OR topups.reference LIKE :search_reference OR topups.paystack_status LIKE :search_provider)';
        $needle = '%' . $search . '%';
        $params['search_email'] = $needle;
        $params['search_name'] = $needle;
        $params['search_username'] = $needle;
        $params['search_reference'] = $needle;
        $params['search_provider'] = $needle;
    }
    if (in_array($status, $allowedStatuses, true)) {
        $sql .= ' AND topups.status = :status';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY topups.created_at DESC, topups.id DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function admin_payment_requests(string $search = '', string $status = ''): array
{
    $allowedStatuses = ['pending', 'approved', 'rejected', 'paid'];
    $presenceSelect = user_presence_select('u');

    $sql = 'SELECT wr.*, u.id AS user_id, u.email, p.display_name, p.username, p.avatar_url,
                   uv.overall_status AS verification_status, ' . $presenceSelect . ',
                   wa.cash_balance, wa.gift_balance, wa.pending_withdrawal
            FROM withdrawal_requests wr
            JOIN users u ON u.id = wr.user_id
            JOIN wallet_accounts wa ON wa.id = wr.wallet_account_id
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN user_verifications uv ON uv.user_id = u.id
            WHERE u.deleted_at IS NULL';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (u.email LIKE :search_email OR p.display_name LIKE :search_name OR p.username LIKE :search_username OR wr.payout_method LIKE :search_method OR wr.payout_details LIKE :search_details)';
        $needle = '%' . $search . '%';
        $params['search_email'] = $needle;
        $params['search_name'] = $needle;
        $params['search_username'] = $needle;
        $params['search_method'] = $needle;
        $params['search_details'] = $needle;
    }
    if (in_array($status, $allowedStatuses, true)) {
        $sql .= ' AND wr.status = :status';
        $params['status'] = $status;
    }

    $sql .= ' ORDER BY FIELD(wr.status, "pending", "approved", "paid", "rejected"), wr.requested_at DESC, wr.id DESC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function admin_payment_request_counts(): array
{
    $counts = ['pending' => 0, 'approved' => 0, 'paid' => 0, 'rejected' => 0];
    $stmt = db()->query('SELECT status, COUNT(*) AS total FROM withdrawal_requests GROUP BY status');
    foreach ($stmt->fetchAll() as $row) {
        $counts[(string) $row['status']] = (int) $row['total'];
    }
    return $counts;
}
