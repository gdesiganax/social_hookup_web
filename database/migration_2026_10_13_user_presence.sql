USE hookup;

ALTER TABLE users
  ADD COLUMN last_seen_at DATETIME NULL AFTER last_login_at,
  ADD INDEX idx_users_last_seen (last_seen_at);
