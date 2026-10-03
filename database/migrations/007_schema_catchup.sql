-- Migration 007 - Align schema with Phase 5 (Admin Panel)
-- Idempotent: every ALTER is guarded by an information_schema check.
-- Safe to run multiple times.

/* ============================================================
   PRODUCTS — add sku, is_active, low_stock_threshold, timestamps
   ============================================================ */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='sku');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD COLUMN sku VARCHAR(64) NULL AFTER product_id', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='is_active');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER image_path', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='low_stock_threshold');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5 AFTER is_active', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='created_at');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='updated_at');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Backfill SKUs for existing rows
UPDATE products
SET sku = CONCAT('SKU-', LPAD(product_id, 6, '0'))
WHERE sku IS NULL OR sku = '';

-- Unique index on sku (guarded)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND INDEX_NAME='uq_products_sku');
SET @s := IF(@c = 0, 'ALTER TABLE products ADD UNIQUE KEY uq_products_sku (sku)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ============================================================
   ORDERS — add order_ref, subtotal, shipping_fee, tax_amount,
            currency, updated_at; expand order_status enum
   ============================================================ */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='order_ref');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN order_ref CHAR(12) NULL AFTER order_id', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='subtotal');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER order_status', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='shipping_fee');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='tax_amount');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER shipping_fee', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='currency');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN currency CHAR(3) NOT NULL DEFAULT ''LSL'' AFTER total_amount', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='updated_at');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Expand order_status enum to include paid / processing / refunded
ALTER TABLE orders
  MODIFY COLUMN order_status
  ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded')
  NOT NULL DEFAULT 'pending';

-- Backfill order_ref for existing orders
UPDATE orders
SET order_ref = CONCAT('ORD-', LPAD(order_id, 8, '0'))
WHERE order_ref IS NULL OR order_ref = '';

-- Unique index on order_ref (guarded)
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND INDEX_NAME='uq_orders_ref');
SET @s := IF(@c = 0, 'ALTER TABLE orders ADD UNIQUE KEY uq_orders_ref (order_ref)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ============================================================
   ORDER_ITEMS — add product_name, product_sku, line_total
   ============================================================ */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='order_items' AND COLUMN_NAME='product_name');
SET @s := IF(@c = 0, 'ALTER TABLE order_items ADD COLUMN product_name VARCHAR(180) NOT NULL DEFAULT '''' AFTER product_id', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='order_items' AND COLUMN_NAME='product_sku');
SET @s := IF(@c = 0, 'ALTER TABLE order_items ADD COLUMN product_sku VARCHAR(64) NOT NULL DEFAULT '''' AFTER product_name', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='order_items' AND COLUMN_NAME='line_total');
SET @s := IF(@c = 0, 'ALTER TABLE order_items ADD COLUMN line_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER unit_price', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Backfill product_name / line_total from products
UPDATE order_items oi
JOIN products p ON p.product_id = oi.product_id
SET oi.product_name = p.name,
    oi.product_sku  = COALESCE(p.sku, CONCAT('SKU-', LPAD(p.product_id, 6, '0'))),
    oi.line_total   = oi.quantity * oi.unit_price
WHERE oi.product_name = '' OR oi.line_total = 0;

/* ============================================================
   PAYMENTS — add provider, provider_ref, idempotency_key, etc.
   ============================================================ */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='provider');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN provider VARCHAR(32) NOT NULL DEFAULT ''mock'' AFTER order_id', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='provider_ref');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN provider_ref VARCHAR(128) NULL AFTER provider', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='idempotency_key');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN idempotency_key CHAR(64) NULL AFTER provider_ref', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='currency');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN currency CHAR(3) NOT NULL DEFAULT ''LSL'' AFTER amount', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='failure_reason');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN failure_reason VARCHAR(255) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='updated_at');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

ALTER TABLE payments
  MODIFY COLUMN payment_status
  ENUM('initiated','pending','authorized','paid','captured','failed','refunded')
  NOT NULL DEFAULT 'initiated';

/* ============================================================
   SUPPORT TABLES (create if missing)
   ============================================================ */

CREATE TABLE IF NOT EXISTS login_attempts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(190) NOT NULL,
  ip_address   VARCHAR(45)  NOT NULL,
  attempted_at DATETIME NOT NULL,
  KEY idx_attempts_email (email, attempted_at),
  KEY idx_attempts_ip    (ip_address, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_sessions (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT(11) NOT NULL,
  session_id  VARCHAR(128) NOT NULL,
  ip_address  VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL,
  UNIQUE KEY uq_session (session_id),
  KEY idx_sessions_user (user_id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_logs (
  log_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT(11) NULL,
  action      VARCHAR(80) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id   VARCHAR(50) NULL,
  before_data JSON NULL,
  after_data  JSON NULL,
  ip_address  VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_logs_user (user_id, created_at),
  KEY idx_logs_entity (entity_type, entity_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_status_history (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT(11) NOT NULL,
  from_status VARCHAR(20) NULL,
  to_status   VARCHAR(20) NOT NULL,
  changed_by  INT(11) NULL,
  note        VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hist_order (order_id, created_at),
  CONSTRAINT fk_hist_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ============================================================
   USERS — verify avatar exists
   ============================================================ */

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='avatar');
SET @s := IF(@c = 0, 'ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ============================================================
   Track migration
   ============================================================ */

CREATE TABLE IF NOT EXISTS migrations (
  id         VARCHAR(120) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO migrations (id) VALUES ('007_schema_catchup');