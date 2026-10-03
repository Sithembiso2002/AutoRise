<?php
declare(strict_types=1);

/**
 * Sync all rows from MySQL into the SQLite mirror.
 *
 * Usage:
 *   php tools/sync_mysql_to_sqlite.php
 *   php tools/sync_mysql_to_sqlite.php --tables=products,orders,order_items
 *
 * Assumes the SQLite file already exists (run build_sqlite.php first).
 * Only mirrors base tables (never views), and skips _legacy_* by default.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$base = dirname(__DIR__);
Dotenv::createImmutable($base)->safeLoad();

$db   = (string) ($_ENV['DB_DATABASE'] ?? 'shop_system');
$host = (string) ($_ENV['DB_HOST'] ?? '127.0.0.1');
$port = (string) ($_ENV['DB_PORT'] ?? '3306');
$user = (string) ($_ENV['DB_USERNAME'] ?? 'root');
$pass = (string) ($_ENV['DB_PASSWORD'] ?? '');

$sqliteRel  = (string) ($_ENV['SQLITE_PATH'] ?? 'database/shop_system.sqlite');
$sqlitePath = $base . DIRECTORY_SEPARATOR . $sqliteRel;

if (!is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite file not found at: $sqlitePath\n");
    fwrite(STDERR, "Run: php tools/build_sqlite.php\n");
    exit(1);
}

/* ---------- Flags ---------- */
$onlyTables    = null;
$includeLegacy = in_array('--include-legacy', $argv, true);

foreach ($argv as $arg) {
    if (preg_match('/^--tables=(.+)$/', $arg, $m)) {
        $onlyTables = array_filter(array_map('trim', explode(',', $m[1])));
    }
}

/* ---------- Connect to MySQL ---------- */
try {
    $mysqlDsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $mysql = new PDO($mysqlDsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "MySQL connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

/* ---------- Connect to SQLite ---------- */
try {
    $sqlite = new PDO('sqlite:' . $sqlitePath);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->exec('PRAGMA foreign_keys = OFF');
    $sqlite->exec('PRAGMA synchronous = NORMAL');
    $sqlite->exec('PRAGMA journal_mode = WAL');
    $sqlite->exec('PRAGMA busy_timeout = 5000');
} catch (PDOException $e) {
    fwrite(STDERR, "SQLite open failed: " . $e->getMessage() . "\n");
    exit(1);
}

/* ---------- List BASE TABLES only (never views) ---------- */
$tables = $mysql
    ->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
    ->fetchAll(PDO::FETCH_COLUMN);

sort($tables);

/* Filter legacy */
if (!$includeLegacy) {
    $tables = array_values(array_filter($tables, fn($t) => !str_starts_with($t, '_legacy_')));
}

/* Filter --tables=... */
if ($onlyTables !== null) {
    $tables = array_values(array_intersect($tables, $onlyTables));
}

if (empty($tables)) {
    fwrite(STDERR, "No tables to sync.\n");
    exit(1);
}

/* ---------- Restrict to tables that exist in SQLite (safe) ---------- */
$sqliteTables = $sqlite
    ->query("SELECT name FROM sqlite_master WHERE type='table'")
    ->fetchAll(PDO::FETCH_COLUMN);

$tablesToSync = array_values(array_intersect($tables, $sqliteTables));
$missingInSqlite = array_diff($tables, $sqliteTables);

if (!empty($missingInSqlite)) {
    echo "Skipping " . count($missingInSqlite) . " table(s) not present in SQLite: "
        . implode(', ', $missingInSqlite) . "\n\n";
}

if (empty($tablesToSync)) {
    fwrite(STDERR, "No matching tables between MySQL and SQLite.\n");
    exit(1);
}

echo "Syncing " . count($tablesToSync) . " tables from MySQL to SQLite...\n\n";

$grandTotal = 0;
$startedAt  = microtime(true);
$failed     = [];

foreach ($tablesToSync as $table) {
    $start = microtime(true);

    try {
        /* Read all rows from MySQL */
        $rows = $mysql->query("SELECT * FROM `$table`")->fetchAll();

        /* Clear existing rows in SQLite */
        $sqlite->exec("DELETE FROM `$table`");

        if (empty($rows)) {
            echo "  . $table — 0 rows\n";
            continue;
        }

        /* Prepare INSERT */
        $cols         = array_keys($rows[0]);
        $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $cols));
        $colList      = implode(', ', array_map(fn($c) => "`$c`", $cols));

        $insert = $sqlite->prepare(
            "INSERT INTO `$table` ($colList) VALUES ($placeholders)"
        );

        $sqlite->beginTransaction();

        $count = 0;
        foreach ($rows as $row) {
            $bind = [];
            foreach ($cols as $c) {
                $v = $row[$c] ?? null;
                if (is_bool($v)) $v = $v ? 1 : 0;
                $bind[':' . $c] = $v;
            }
            $insert->execute($bind);
            $count++;
        }

        $sqlite->commit();

        $ms = round((microtime(true) - $start) * 1000);
        echo "  + $table — $count rows ({$ms}ms)\n";
        $grandTotal += $count;
    } catch (\Throwable $e) {
        if ($sqlite->inTransaction()) {
            $sqlite->rollBack();
        }
        $failed[] = $table;
        echo "  x $table — " . $e->getMessage() . "\n";
    }
}

$sqlite->exec('PRAGMA foreign_keys = ON');

/* ---------- Summary ---------- */
$elapsed = round(microtime(true) - $startedAt, 2);
$size    = number_format(filesize($sqlitePath) / 1024, 1);

echo "\n";
echo "Synced $grandTotal rows across " . count($tablesToSync) . " tables in {$elapsed}s.\n";
echo "SQLite file: $sqlitePath ({$size} KB)\n";

if (!empty($failed)) {
    echo "\nFailed tables (" . count($failed) . "): " . implode(', ', $failed) . "\n";
    exit(1);
}

echo "Done.\n";