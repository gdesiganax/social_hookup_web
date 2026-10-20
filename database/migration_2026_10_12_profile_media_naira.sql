USE hookup;

ALTER TABLE profiles
  ADD COLUMN profile_visibility ENUM('public', 'matches', 'private') NOT NULL DEFAULT 'public' AFTER looking_for;

CREATE TABLE IF NOT EXISTS profile_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  media_type ENUM('image', 'video') NOT NULL,
  media_url VARCHAR(700) NOT NULL,
  caption VARCHAR(180) NULL,
  visibility ENUM('public', 'private') NOT NULL DEFAULT 'public',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_profile_media_user_visibility (user_id, visibility, created_at)
) ENGINE=InnoDB;

UPDATE gift_catalog SET price = 2500.00, redeemable_amount = 2125.00 WHERE id = 1;
UPDATE gift_catalog SET price = 7500.00, redeemable_amount = 6375.00 WHERE id = 2;
UPDATE gift_catalog SET price = 15000.00, redeemable_amount = 12750.00 WHERE id = 3;
UPDATE gift_catalog SET price = 30000.00, redeemable_amount = 25500.00 WHERE id = 4;

UPDATE gift_catalog SET icon = 'bi-cup-hot-fill' WHERE id = 1;
UPDATE gift_catalog SET icon = 'bi-flower1' WHERE id = 2;
UPDATE gift_catalog SET icon = 'bi-egg-fried' WHERE id = 3;
UPDATE gift_catalog SET icon = 'bi-stars' WHERE id = 4;

UPDATE wallet_accounts
SET cash_balance = CASE WHEN cash_balance < 30000 THEN 100000.00 ELSE cash_balance END;
