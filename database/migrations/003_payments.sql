-- Migration 003 - Extend payments for gateway integration
-- Safe to re-run

-- Add columns if missing
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='provider');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN provider VARCHAR(32) NOT NULL DEFAULT "mock" AFTER order_id',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='provider_ref');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN provider_ref VARCHAR(128) NULL AFTER provider',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='idempotency_key');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN idempotency_key CHAR(64) NULL AFTER provider_ref',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='currency');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN currency CHAR(3) NOT NULL DEFAULT "LSL" AFTER amount',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='updated_at');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='failure_reason');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD COLUMN failure_reason VARCHAR(255) NULL',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Unique index on idempotency_key (guarded)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='uq_payment_idem');
SET @s := IF(@c=0,
  'ALTER TABLE payments ADD UNIQUE KEY uq_payment_idem (idempotency_key)',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- payment_status should allow 'initiated' too
ALTER TABLE payments
  MODIFY COLUMN payment_status
  ENUM('initiated','pending','authorized','paid','captured','failed','refunded')
  NOT NULL DEFAULT 'initiated';

-- Track migration
INSERT IGNORE INTO migrations (id) VALUES ('003_payments');