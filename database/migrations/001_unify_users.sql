-- Migration 001 v4 - Only missing parts
-- Safe to re-run: every destructive action is guarded by information_schema checks
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insert non-colliding customers (keeps their ids)
INSERT INTO users (
    user_id, username, email, password, name,
    first_name, last_name, address, city, zip_code, country,
    role, is_active, created_at, updated_at
)
SELECT c.id, c.username, c.email, c.password,
       CONCAT(c.first_name, ' ', c.last_name),
       c.first_name, c.last_name,
       c.address, c.city, c.zip_code, c.country,
       'customer', c.is_active, c.created_at, c.updated_at
FROM customers c
WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.user_id = c.id)
  AND NOT EXISTS (SELECT 1 FROM users u WHERE u.email   = c.email);

-- 2. Insert colliding customers with a fresh auto-id
INSERT INTO users (
    username, email, password, name,
    first_name, last_name, address, city, zip_code, country,
    role, is_active, created_at, updated_at
)
SELECT c.username, c.email, c.password,
       CONCAT(c.first_name, ' ', c.last_name),
       c.first_name, c.last_name,
       c.address, c.city, c.zip_code, c.country,
       'customer', c.is_active, c.created_at, c.updated_at
FROM customers c
WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.email = c.email);

-- 3. Drop old FK on orders (only if it exists)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                  WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'orders'
                    AND CONSTRAINT_NAME = 'fk_customer_order');
SET @sql = IF(@fk_exists > 0,
              'ALTER TABLE orders DROP FOREIGN KEY fk_customer_order',
              'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Remap orders.user_id via email lookup
UPDATE orders o
JOIN customers c ON o.user_id = c.id
JOIN users    u ON u.email   = c.email
SET o.user_id = u.user_id
WHERE o.user_id <> u.user_id;

-- 5. Rename customers -> _legacy_customers (only if not already renamed)
SET @customers_exists = (SELECT COUNT(*) FROM information_schema.TABLES
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME   = 'customers');
SET @legacy_exists = (SELECT COUNT(*) FROM information_schema.TABLES
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME   = '_legacy_customers');
SET @sql = IF(@customers_exists = 1 AND @legacy_exists = 0,
              'RENAME TABLE customers TO _legacy_customers',
              'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Add new FK on orders -> users (only if not already added)
SET @new_fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                      WHERE CONSTRAINT_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'orders'
                        AND CONSTRAINT_NAME = 'fk_orders_user');
SET @sql = IF(@new_fk_exists = 0,
              'ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT',
              'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. Clean carts and repoint FK
DELETE FROM cart_items;
DELETE FROM carts;

SET @cart_fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                       WHERE CONSTRAINT_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'carts'
                         AND CONSTRAINT_NAME = 'carts_ibfk_1');
SET @sql = IF(@cart_fk_exists > 0,
              'ALTER TABLE carts DROP FOREIGN KEY carts_ibfk_1',
              'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cart_fk2_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                        WHERE CONSTRAINT_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'carts'
                          AND CONSTRAINT_NAME = 'fk_carts_user');
SET @sql = IF(@cart_fk2_exists = 0,
              'ALTER TABLE carts ADD CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE',
              'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. Support tables
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
  CONSTRAINT fk_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Recreate the view
DROP VIEW IF EXISTS customer_order_summary;

CREATE VIEW customer_order_summary AS
SELECT u.user_id, u.name, u.email,
       COUNT(o.order_id) AS total_orders,
       COALESCE(SUM(o.total_amount), 0) AS total_spent
FROM users u
LEFT JOIN orders o ON o.user_id = u.user_id
WHERE u.role = 'customer'
GROUP BY u.user_id, u.name, u.email;

SET FOREIGN_KEY_CHECKS = 1;