USE hookup;

CREATE TABLE IF NOT EXISTS wallet_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  cash_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  gift_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  pending_withdrawal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_wallet_nonnegative CHECK (cash_balance >= 0 AND gift_balance >= 0 AND pending_withdrawal >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gift_catalog (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  icon VARCHAR(16) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  redeemable_amount DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_gift_catalog_amounts CHECK (price > 0 AND redeemable_amount > 0 AND redeemable_amount <= price)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gift_purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  gift_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  redeemable_amount DECIMAL(10,2) NOT NULL,
  status ENUM('completed', 'refunded') NOT NULL DEFAULT 'completed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gift_purchases_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_gift_purchases_gift FOREIGN KEY (gift_id) REFERENCES gift_catalog(id),
  CONSTRAINT fk_gift_purchases_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_gift_purchases_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_gift_purchases_conversation (conversation_id, created_at),
  INDEX idx_gift_purchases_recipient (recipient_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_account_id BIGINT UNSIGNED NOT NULL,
  related_user_id BIGINT UNSIGNED NULL,
  gift_purchase_id BIGINT UNSIGNED NULL,
  transaction_type ENUM('seed_credit', 'gift_sent', 'gift_received', 'gift_conversion', 'withdrawal_request') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  balance_type ENUM('cash', 'gift', 'pending_withdrawal') NOT NULL,
  description VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_transactions_wallet FOREIGN KEY (wallet_account_id) REFERENCES wallet_accounts(id) ON DELETE CASCADE,
  CONSTRAINT fk_wallet_transactions_related_user FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_wallet_transactions_gift FOREIGN KEY (gift_purchase_id) REFERENCES gift_purchases(id) ON DELETE SET NULL,
  INDEX idx_wallet_transactions_wallet (wallet_account_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS withdrawal_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  wallet_account_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payout_method VARCHAR(80) NOT NULL,
  payout_details VARCHAR(255) NOT NULL,
  status ENUM('pending', 'approved', 'rejected', 'paid') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  CONSTRAINT fk_withdrawals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_withdrawals_wallet FOREIGN KEY (wallet_account_id) REFERENCES wallet_accounts(id) ON DELETE CASCADE,
  INDEX idx_withdrawals_status (status)
) ENGINE=InnoDB;

INSERT IGNORE INTO wallet_accounts (user_id, cash_balance, gift_balance)
SELECT id, 100.00, 0.00 FROM users WHERE deleted_at IS NULL;

INSERT IGNORE INTO gift_catalog (id, name, icon, price, redeemable_amount, is_active) VALUES
(1, 'Coffee Date', 'bi-cup-hot-fill', 10.00, 8.50, 1),
(2, 'Rose Bouquet', 'bi-flower1', 25.00, 21.25, 1),
(3, 'Dinner Treat', 'bi-egg-fried', 50.00, 42.50, 1),
(4, 'Weekend Spark', 'bi-stars', 100.00, 85.00, 1);
