<?php
declare(strict_types=1);

/**
 * Clear temporary caches.
 *
 * Usage:
 *   php tools/cache.php            (clears view + config caches)
 *   php tools/cache.php --views    (only views)
 *   php tools/cache.php --logs     (also truncates old log files)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$base = dirname(__DIR__);

$clearViews = true;
$clearLogs  = false;

foreach ($argv as $arg) {
    if ($arg === '--views') { $clearViews = true;  $clearLogs = false; }
    if ($arg === '--logs')  { $clearLogs  = true; }
}

if ($clearViews) {
    $cacheDir = $base . '/storage/cache';
    if (is_dir($cacheDir)) {
        $count = 0;
        foreach (glob($cacheDir . '/*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) $count++;
        }
        echo "Cleared {$count} cache file(s).\n";
    } else {
        echo "No cache directory.\n";
    }
}

if ($clearLogs) {
    $logDir = $base . '/storage/logs';
    $cutoff = time() - 86400 * 14; // 14 days
    $count = 0;
    foreach (glob($logDir . '/*.log') ?: [] as $file) {
        if (is_file($file) && filemtime($file) < $cutoff && @unlink($file)) $count++;
    }
    echo "Removed {$count} old log file(s).\n";
}

echo "Done.\n";