USE hookup;

CREATE TABLE IF NOT EXISTS user_verifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  phone_number VARCHAR(40) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  city VARCHAR(120) NOT NULL,
  state VARCHAR(120) NOT NULL,
  nin_number VARCHAR(20) NOT NULL,
  government_id_type ENUM('nin') NOT NULL DEFAULT 'nin',
  government_id_path VARCHAR(700) NOT NULL,
  government_id_original_name VARCHAR(255) NULL,
  government_id_mime VARCHAR(120) NOT NULL,
  phone_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  address_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  government_id_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  overall_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  rejection_reason VARCHAR(255) NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_verifications_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_verifications_status (overall_status, submitted_at)
) ENGINE=InnoDB;
