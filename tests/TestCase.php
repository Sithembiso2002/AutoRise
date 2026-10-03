<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Schema is created only once per test run. */
    private static bool $schemaCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        /* Reset session between tests */
        $_SESSION = [];

        /* Create schema on first test */
        if (!self::$schemaCreated) {
            $this->createSchema();
            self::$schemaCreated = true;
        }

        /* Every test starts with clean tables */
        $this->wipeTables();
    }

    /* ============================================================
       Schema — created once, shared across all tests
       ============================================================ */

    private function createSchema(): void
    {
        $pdo = Database::connection('sqlite')->pdo();

        $pdo->exec('PRAGMA foreign_keys = OFF');
        $pdo->exec('PRAGMA journal_mode = WAL');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT NULL,
                name          TEXT NOT NULL,
                first_name    TEXT NULL,
                last_name     TEXT NULL,
                email         TEXT NOT NULL UNIQUE,
                password      TEXT NOT NULL,
                role          TEXT NOT NULL DEFAULT 'customer',
                is_active     INTEGER NOT NULL DEFAULT 1,
                avatar        TEXT NULL,
                phone         TEXT NULL,
                address       TEXT NULL,
                city          TEXT NULL,
                zip_code      TEXT NULL,
                country       TEXT NULL,
                last_login_at TEXT NULL,
                created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                product_id          INTEGER PRIMARY KEY AUTOINCREMENT,
                sku                 TEXT NULL,
                name                TEXT NOT NULL,
                description         TEXT NULL,
                price               NUMERIC NOT NULL DEFAULT 0,
                stock               INTEGER NOT NULL DEFAULT 0,
                category_id         INTEGER NULL,
                image_path          TEXT NULL,
                is_active           INTEGER NOT NULL DEFAULT 1,
                low_stock_threshold INTEGER NOT NULL DEFAULT 5,
                created_at          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                order_id     INTEGER PRIMARY KEY AUTOINCREMENT,
                order_ref    TEXT NULL,
                user_id      INTEGER NULL,
                order_date   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                order_status TEXT NOT NULL DEFAULT 'pending',
                subtotal     NUMERIC NOT NULL DEFAULT 0,
                shipping_fee NUMERIC NOT NULL DEFAULT 0,
                tax_amount   NUMERIC NOT NULL DEFAULT 0,
                total_amount NUMERIC NOT NULL DEFAULT 0,
                currency     TEXT NOT NULL DEFAULT 'LSL',
                updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                order_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id      INTEGER NOT NULL,
                product_id    INTEGER NULL,
                product_name  TEXT NOT NULL DEFAULT '',
                product_sku   TEXT NOT NULL DEFAULT '',
                quantity      INTEGER NOT NULL DEFAULT 1,
                price         NUMERIC NOT NULL DEFAULT 0,
                unit_price    NUMERIC NOT NULL DEFAULT 0,
                line_total    NUMERIC NOT NULL DEFAULT 0
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                email        TEXT NOT NULL,
                ip_address   TEXT NOT NULL,
                attempted_at TEXT NOT NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                bucket     TEXT NOT NULL,
                identifier TEXT NOT NULL,
                hits       INTEGER NOT NULL DEFAULT 0,
                reset_at   TEXT NOT NULL,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (bucket, identifier)
            )
        ");

        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    /* ============================================================
       Wipe data between tests
       ============================================================ */

    private function wipeTables(): void
    {
        $pdo = Database::connection('sqlite')->pdo();
        $pdo->exec('PRAGMA foreign_keys = OFF');

        $tables = [
            'rate_limits',
            'login_attempts',
            'order_items',
            'orders',
            'products',
            'users',
        ];

        foreach ($tables as $t) {
            try {
                $pdo->exec("DELETE FROM {$t}");
            } catch (\Throwable) {
                // table may not exist — ignore
            }
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
    }
}