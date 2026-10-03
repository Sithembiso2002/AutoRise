-- Migration 008 - Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_pr_email (email, expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS migrations (
  id         VARCHAR(120) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO migrations (id) VALUES ('008_password_resets');