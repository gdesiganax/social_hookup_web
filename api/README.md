# HookUp REST API

All endpoints return JSON and live under `/api`.

## Authentication

Register:

```http
POST /api/users.php?action=register
Content-Type: application/json
```

Login:

```http
POST /api/users.php?action=login
Content-Type: application/json
```

Use the returned token on protected requests:

```http
Authorization: Bearer YOUR_TOKEN
```

## Table Endpoints

Each database table has a matching endpoint file:

- `users.php`
- `profiles.php`
- `profile_media.php`
- `user_verifications.php`
- `interests.php`
- `profile_interests.php`
- `matches.php`
- `conversations.php`
- `conversation_members.php`
- `messages.php`
- `posts.php`
- `post_reactions.php`
- `post_comments.php`
- `wallet_accounts.php`
- `gift_catalog.php`
- `gift_purchases.php`
- `wallet_transactions.php`
- `wallet_topups.php`
- `withdrawal_requests.php`
- `dates.php`
- `reports.php`
- `settings.php`

Most table endpoints support:

- `GET /api/table.php`
- `GET /api/table.php?id=1`
- `POST /api/table.php`
- `PATCH /api/table.php?id=1`
- `DELETE /api/table.php?id=1`

Important app actions:

- `GET /api/profiles.php?action=discovery`
- `GET /api/profiles.php?action=public`
- `GET /api/posts.php?action=feed`
- `POST /api/matches.php?action=match`
- `GET /api/matches.php?action=mine`
- `POST /api/conversations.php?action=direct`
- `GET /api/conversations.php?action=mine`
- `GET /api/messages.php?action=conversation&conversation_id=1`
- `POST /api/messages.php`
- `POST /api/post_reactions.php`
- `DELETE /api/post_reactions.php?post_id=1`
- `GET /api/post_comments.php?action=for_post&post_id=1`
- `GET /api/wallet_accounts.php?action=mine`
- `POST /api/wallet_accounts.php?action=convert_gifts`
- `GET /api/wallet_transactions.php?action=mine`
- `POST /api/wallet_topups.php?action=fund`
- `POST /api/gift_purchases.php?action=send`
- `GET /api/gift_purchases.php?action=conversation&conversation_id=1`
- `POST /api/withdrawal_requests.php?action=request`
- `GET /api/dates.php?action=upcoming`

