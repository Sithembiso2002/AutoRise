-- Migration 009 - Rate limit hits
CREATE TABLE IF NOT EXISTS rate_limits (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket     VARCHAR(120) NOT NULL,
  identifier VARCHAR(120) NOT NULL,
  hits       INT UNSIGNED NOT NULL DEFAULT 0,
  reset_at   DATETIME NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bucket_ident (bucket, identifier),
  KEY idx_rate_reset (reset_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS migrations (
  id         VARCHAR(120) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO migrations (id) VALUES ('009_rate_limits');