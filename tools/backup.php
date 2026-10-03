<?php
declare(strict_types=1);

/**
 * Backup the shop_system MySQL database to a timestamped .sql file.
 *
 * Usage:
 *   php tools/backup.php
 *   php tools/backup.php --keep=30   (keep last 30 daily backups; default 14)
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

$keep = 14;
foreach ($argv as $arg) {
    if (preg_match('/^--keep=(\d+)$/', $arg, $m)) {
        $keep = max(1, (int) $m[1]);
    }
}

$dir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($dir)) mkdir($dir, 0775, true);

$stamp = date('Ymd_His');
$file  = $dir . DIRECTORY_SEPARATOR . $db . '_' . $stamp . '.sql';

// Build mysqldump command
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!is_file($mysqldump)) {
    // Fallback to PATH
    $mysqldump = 'mysqldump';
}

$cmd = sprintf(
    '%s -h %s -P %s -u %s %s %s > %s',
    escapeshellarg($mysqldump),
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    $pass !== '' ? '-p' . escapeshellarg($pass) : '',
    escapeshellarg($db),
    escapeshellarg($file)
);

exec($cmd, $out, $code);

if ($code !== 0 || !is_file($file) || filesize($file) < 100) {
    fwrite(STDERR, "Backup FAILED (exit {$code}). File: {$file}\n");
    exit(1);
}

$size = number_format(filesize($file) / 1024, 1);
echo "Backup OK: {$file} ({$size} KB)\n";

// Prune old backups
$files = glob($dir . DIRECTORY_SEPARATOR . $db . '_*.sql') ?: [];
usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

$deleted = 0;
foreach (array_slice($files, $keep) as $old) {
    if (@unlink($old)) $deleted++;
}

if ($deleted > 0) {
    echo "Pruned {$deleted} old backup(s).\n";
}

echo "Done.\n";