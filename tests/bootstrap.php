<?php
declare(strict_types=1);

/**
 * Test bootstrap.
 *
 * - Loads the Composer autoloader
 * - Initialises the App container so App::basePath() works
 * - Uses a real on-disk SQLite file for tests (not :memory:)
 *   so multiple connections within a single test share the same data
 * - Cleans up the test database on shutdown
 */

$base = dirname(__DIR__);

require $base . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Config;
use Dotenv\Dotenv;

/*
 * Initialise App first — its constructor sets the static basePath
 * that App::basePath() and base_path() rely on.
 */
new App($base);

/* Load .env (safe if missing) */
Dotenv::createImmutable($base)->safeLoad();

/* Ensure storage/ exists */
$storageDir = $base . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

/* Test database file */
$testDb = $storageDir . '/test.sqlite';
if (is_file($testDb)) {
    @unlink($testDb);
}

/* Force test-friendly overrides */
$_ENV['APP_ENV']       = 'testing';
$_ENV['APP_DEBUG']     = 'true';
$_ENV['APP_NAME']      = 'Auto Rise';
$_ENV['APP_URL']       = 'http://localhost/BuyCar/public';
$_ENV['APP_KEY']       = $_ENV['APP_KEY'] ?? ('base64:' . base64_encode(random_bytes(32)));
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['SQLITE_PATH']   = 'storage/test.sqlite';

/* Load config now that App::basePath() works */
Config::load($base);

/* Session stub */
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* Cleanup on shutdown */
register_shutdown_function(function () use ($testDb) {
    if (is_file($testDb)) {
        @unlink($testDb);
    }
    // Also clean WAL/SHM companions
    foreach (['.sqlite-wal', '.sqlite-shm'] as $ext) {
        $f = preg_replace('/\.sqlite$/', $ext, $testDb);
        if ($f !== null && is_file($f)) @unlink($f);
    }
});