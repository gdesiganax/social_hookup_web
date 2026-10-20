USE hookup;

ALTER TABLE posts
  ADD COLUMN media_type ENUM('image', 'video') NULL AFTER body,
  ADD COLUMN media_url VARCHAR(700) NULL AFTER media_type;

ALTER TABLE wallet_transactions
  MODIFY transaction_type ENUM('seed_credit', 'wallet_funding', 'gift_sent', 'gift_received', 'gift_conversion', 'withdrawal_request') NOT NULL;

CREATE TABLE IF NOT EXISTS wallet_topups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payable_amount DECIMAL(12,2) NOT NULL,
  reference VARCHAR(80) NOT NULL UNIQUE,
  access_code VARCHAR(120) NULL,
  authorization_url VARCHAR(700) NULL,
  status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
  paystack_status VARCHAR(80) NULL,
  provider_payload JSON NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_topups_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wallet_topups_user_created (user_id, created_at),
  INDEX idx_wallet_topups_status (status)
) ENGINE=InnoDB;
