<?php
declare(strict_types=1);

/**
 * Build a fresh SQLite mirror of the MySQL schema.
 *
 * Usage:
 *   php tools/build_sqlite.php
 *   php tools/build_sqlite.php --include-legacy   (also mirror _legacy_* tables)
 *
 * Reads the MySQL schema via mysqldump --no-data, translates each
 * CREATE TABLE to SQLite-compatible syntax, and writes the result
 * to database/shop_system.sqlite.
 *
 * Safe to re-run: wipes the existing SQLite file first.
 *
 * NOTE: ENUM columns become plain TEXT in SQLite. MySQL enforces the
 * allowed values; SQLite stores whatever MySQL has. No CHECK constraints
 * are generated — they were causing validation failures during sync.
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

/* ---------- Flags ---------- */
$includeLegacy = in_array('--include-legacy', $argv, true);

/* ---------- Locate mysqldump ---------- */
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!is_file($mysqldump)) {
    $which = shell_exec('where mysqldump 2>NUL');
    if ($which) {
        $mysqldump = trim(strtok($which, "\n"));
    } else {
        $mysqldump = 'mysqldump';
    }
}

/* ---------- Dump schema ---------- */
$cmd = escapeshellarg($mysqldump)
     . ' --no-data --skip-comments --skip-set-charset'
     . ' --routines=0 --triggers=0 --events=0'
     . ' -h ' . escapeshellarg($host)
     . ' -P ' . escapeshellarg($port)
     . ' -u ' . escapeshellarg($user)
     . ($pass !== '' ? ' -p' . escapeshellarg($pass) : '')
     . ' ' . escapeshellarg($db);

$output   = [];
$exitCode = 0;
exec($cmd, $output, $exitCode);

if ($exitCode !== 0 || empty($output)) {
    fwrite(STDERR, "mysqldump failed (exit $exitCode).\nCommand: $cmd\n");
    exit(1);
}

$dump = implode("\n", $output);

/* ---------- Delete existing SQLite file ---------- */
if (is_file($sqlitePath)) {
    unlink($sqlitePath);
    echo "Removed old SQLite file.\n";
}

$sqliteDir = dirname($sqlitePath);
if (!is_dir($sqliteDir)) mkdir($sqliteDir, 0775, true);

/* ---------- Open SQLite ---------- */
$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->exec('PRAGMA foreign_keys = OFF');
$sqlite->exec('PRAGMA journal_mode = WAL');

/* ---------- Parse each CREATE TABLE ---------- */
preg_match_all(
    '/CREATE TABLE `(\w+)` \((.*?)\)\s*ENGINE[^;]*;/si',
    $dump,
    $tables,
    PREG_SET_ORDER
);

if (empty($tables)) {
    fwrite(STDERR, "No CREATE TABLE statements found in dump.\n");
    exit(1);
}

$created  = 0;
$skipped  = [];
$indexSql = [];

foreach ($tables as $t) {
    $tableName = $t[1];
    $body      = $t[2];

    /* Skip legacy tables unless explicitly asked */
    if (!$includeLegacy && str_starts_with($tableName, '_legacy_')) {
        $skipped[] = $tableName;
        continue;
    }

    [$ddl, $indexes] = convertTableToSqlite($tableName, $body);

    try {
        $sqlite->exec($ddl);
        $created++;
        foreach ($indexes as $idx) {
            $indexSql[] = $idx;
        }
        echo "  + $tableName\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  x $tableName: " . $e->getMessage() . "\n");
    }
}

/* ---------- Create indexes after all tables exist ---------- */
$idxCreated = 0;
foreach ($indexSql as $sql) {
    try {
        $sqlite->exec($sql);
        $idxCreated++;
    } catch (\Throwable) {
        // ignore index collisions
    }
}

$sqlite->exec('PRAGMA foreign_keys = ON');

/* ---------- Summary ---------- */
echo "\n";
echo "Built $created tables, $idxCreated indexes.\n";

if (!empty($skipped)) {
    echo "Skipped " . count($skipped) . " legacy table(s): " . implode(', ', $skipped) . "\n";
    echo "  (rerun with --include-legacy to include them)\n";
}

echo "SQLite file: $sqlitePath\n";
echo "Size: " . number_format(filesize($sqlitePath) / 1024, 1) . " KB\n";

/* ============================================================
   Helpers
   ============================================================ */

/**
 * Convert a MySQL CREATE TABLE body to SQLite-compatible DDL.
 * Returns [DDL string, list of CREATE INDEX statements].
 */
function convertTableToSqlite(string $table, string $body): array
{
    $lines = preg_split('/\r?\n/', $body);

    $colDefs     = [];
    $constraints = [];
    $indexes     = [];

    /* Which column has AUTO_INCREMENT? (only one per table) */
    $autoIncCol = null;
    if (preg_match('/`(\w+)`[^,]*AUTO_INCREMENT/i', $body, $m)) {
        $autoIncCol = $m[1];
    }

    foreach ($lines as $line) {
        $line = trim($line, " \t\n\r,");
        if ($line === '') continue;

        /* ---------- Column definition ---------- */
        if (preg_match('/^`(\w+)`\s+(.+)$/s', $line, $m)) {
            $colName = $m[1];
            $colDef  = trim($m[2]);

            /* AUTO_INCREMENT → INTEGER PRIMARY KEY AUTOINCREMENT */
            if (stripos($colDef, 'AUTO_INCREMENT') !== false) {
                $colDefs[] = "`$colName` INTEGER PRIMARY KEY AUTOINCREMENT";
                continue;
            }

            /* ENUM(...) → TEXT (no CHECK — MySQL enforces it) */
            $colDef = preg_replace('/^enum\([^)]+\)/i', 'TEXT', $colDef);

            /* SET(...) → TEXT */
            $colDef = preg_replace('/^set\([^)]+\)/i', 'TEXT', $colDef);

            /* Type mapping */
            $colDef = convertMysqlType($colDef);

            /* Strip MySQL-only clauses */
            $colDef = preg_replace('/\bunsigned\b/i', '', $colDef);
            $colDef = preg_replace('/\bzerofill\b/i', '', $colDef);
            $colDef = preg_replace('/AUTO_INCREMENT/i', '', $colDef);
            $colDef = preg_replace('/CHARACTER SET \w+/i', '', $colDef);
            $colDef = preg_replace('/COLLATE \w+/i', '', $colDef);
            $colDef = preg_replace('/ON UPDATE CURRENT_TIMESTAMP(?:\(\d*\))?/i', '', $colDef);
            $colDef = preg_replace("/COMMENT '(?:[^'\\\\]|\\\\.)*'/i", '', $colDef);
            $colDef = preg_replace('/CURRENT_TIMESTAMP(?:\(\d*\))?/i', 'CURRENT_TIMESTAMP', $colDef);

            $colDef = trim(preg_replace('/\s+/', ' ', $colDef));

            $colDefs[] = "`$colName` $colDef";
            continue;
        }

        /* ---------- PRIMARY KEY ---------- */
        if (preg_match('/^PRIMARY KEY \(([^)]+)\)/i', $line, $pm)) {
            /* Skip table-level PRIMARY KEY if an AUTO_INCREMENT column already owns it */
            if ($autoIncCol === null) {
                $constraints[] = 'PRIMARY KEY (' . $pm[1] . ')';
            }
            continue;
        }

        /* ---------- UNIQUE KEY ---------- */
        if (preg_match('/^UNIQUE KEY `(\w+)` \(([^)]+)\)/i', $line, $um)) {
            $indexes[] = "CREATE UNIQUE INDEX IF NOT EXISTS `$um[1]` ON `$table` ($um[2])";
            continue;
        }

        /* ---------- Regular KEY / INDEX ---------- */
        if (preg_match('/^(?:KEY|INDEX) `(\w+)` \(([^)]+)\)/i', $line, $km)) {
            $indexes[] = "CREATE INDEX IF NOT EXISTS `$km[1]` ON `$table` ($km[2])";
            continue;
        }

        /* ---------- FOREIGN KEY ---------- */
        if (preg_match(
            '/^CONSTRAINT `\w+` FOREIGN KEY \(([^)]+)\) REFERENCES `(\w+)` \(([^)]+)\)(.*)$/i',
            $line,
            $fm
        )) {
            $onDelete = '';
            if (preg_match('/ON DELETE (CASCADE|SET NULL|RESTRICT|NO ACTION)/i', $fm[4], $om)) {
                $onDelete = ' ON DELETE ' . strtoupper($om[1]);
            }
            $constraints[] = 'FOREIGN KEY (' . $fm[1] . ') REFERENCES `' . $fm[2] . '` (' . $fm[3] . ')' . $onDelete;
            continue;
        }

        /* ---------- Skip FULLTEXT ---------- */
        if (preg_match('/^FULLTEXT/i', $line)) continue;
    }

    $allDefs = array_merge($colDefs, $constraints);

    $ddl = "CREATE TABLE IF NOT EXISTS `$table` (\n  "
         . implode(",\n  ", $allDefs) . "\n)";

    return [$ddl, $indexes];
}

/**
 * Convert a MySQL column type token to a SQLite-compatible type.
 */
function convertMysqlType(string $colDef): string
{
    $typePattern = '/\b(tinyint|smallint|mediumint|bigint|int|decimal|numeric|float|double|datetime|timestamp|date|time|year|json|longtext|mediumtext|tinytext|text|longblob|mediumblob|blob|varchar|char)\b(\(\d+(?:,\s*\d+)?\))?/i';

    $map = [
        'tinyint'    => 'INTEGER',
        'smallint'   => 'INTEGER',
        'mediumint'  => 'INTEGER',
        'bigint'     => 'INTEGER',
        'int'        => 'INTEGER',
        'decimal'    => 'NUMERIC',
        'numeric'    => 'NUMERIC',
        'float'      => 'REAL',
        'double'     => 'REAL',
        'datetime'   => 'TEXT',
        'timestamp'  => 'TEXT',
        'date'       => 'TEXT',
        'time'       => 'TEXT',
        'year'       => 'INTEGER',
        'json'       => 'TEXT',
        'longtext'   => 'TEXT',
        'mediumtext' => 'TEXT',
        'tinytext'   => 'TEXT',
        'text'       => 'TEXT',
        'longblob'   => 'BLOB',
        'mediumblob' => 'BLOB',
        'blob'       => 'BLOB',
        'varchar'    => 'TEXT',
        'char'       => 'TEXT',
    ];

    return preg_replace_callback(
        $typePattern,
        function ($m) use ($map) {
            $token = strtolower($m[1]);
            return $map[$token] ?? $m[0];
        },
        $colDef,
        1
    );
}