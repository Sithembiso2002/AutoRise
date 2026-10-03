-- Migration 006 - Add admin columns to products table
-- Idempotent: safe to run multiple times

-- sku
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='sku');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD COLUMN sku VARCHAR(64) NULL AFTER product_id',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- is_active
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='is_active');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER image_path',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- low_stock_threshold
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='low_stock_threshold');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5 AFTER is_active',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- created_at
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='created_at');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- updated_at
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='updated_at');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Backfill SKUs for existing rows
UPDATE products
SET sku = CONCAT('SKU-', LPAD(product_id, 6, '0'))
WHERE sku IS NULL OR sku = '';

-- Make sku unique (only if index doesn't exist)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND INDEX_NAME='uq_products_sku');
SET @s := IF(@c = 0,
  'ALTER TABLE products ADD UNIQUE KEY uq_products_sku (sku)',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Track migration
CREATE TABLE IF NOT EXISTS migrations (
  id         VARCHAR(120) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO migrations (id) VALUES ('006_product_admin_columns');