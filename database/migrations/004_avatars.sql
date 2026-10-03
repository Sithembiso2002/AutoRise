-- Migration 004 - User avatar
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='avatar');
SET @s := IF(@c=0,
  'ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER email',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

INSERT IGNORE INTO migrations (id) VALUES ('004_avatars');