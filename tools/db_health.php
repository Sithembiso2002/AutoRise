<?php
declare(strict_types=1);

/**
 * Quick health check: compare row counts between MySQL and SQLite.
 *
 * Usage:
 *   php tools/db_health.php
 *
 * The `migrations` table is ignored — it's bookkeeping and may differ
 * between engines if migrations were applied at different times.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$base = dirname(__DIR__);
Dotenv::createImmutable($base)->safeLoad();

$mysqlHost = (string) ($_ENV['DB_HOST'] ?? '127.0.0.1');
$mysqlPort = (string) ($_ENV['DB_PORT'] ?? '3306');
$mysqlDb   = (string) ($_ENV['DB_DATABASE'] ?? 'shop_system');
$mysqlUser = (string) ($_ENV['DB_USERNAME'] ?? 'root');
$mysqlPass = (string) ($_ENV['DB_PASSWORD'] ?? '');

$sqlitePath = $base . DIRECTORY_SEPARATOR . (string) ($_ENV['SQLITE_PATH'] ?? 'database/shop_system.sqlite');

$mysql = new PDO("mysql:host=$mysqlHost;port=$mysqlPort;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $mysql
    ->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
    ->fetchAll(PDO::FETCH_COLUMN);
sort($tables);

$ignore = ['migrations', '_legacy_customers'];

printf("%-25s %10s %10s %8s\n", 'TABLE', 'MYSQL', 'SQLITE', 'MATCH');
echo str_repeat('-', 58) . "\n";

$ok = 0; $mismatch = 0; $missing = 0; $skipped = 0;

foreach ($tables as $t) {
    if (in_array($t, $ignore, true) || str_starts_with($t, '_legacy_')) {
        $skipped++;
        continue;
    }

    $mCount = (int) $mysql->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();

    try {
        $sCount = (int) $sqlite->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    } catch (\Throwable) {
        printf("%-25s %10d %10s %8s\n", $t, $mCount, 'MISSING', 'X');
        $missing++;
        continue;
    }

    $match = $mCount === $sCount ? 'OK' : 'DIFF';
    if ($mCount === $sCount) $ok++;
    else $mismatch++;

    printf("%-25s %10d %10d %8s\n", $t, $mCount, $sCount, $match);
}

echo "\n";
echo "Match:    $ok\n";
echo "Mismatch: $mismatch\n";
echo "Missing:  $missing\n";
echo "Skipped:  $skipped (bookkeeping tables)\n";

exit($mismatch + $missing > 0 ? 1 : 0);