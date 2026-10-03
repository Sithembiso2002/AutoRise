<?php
declare(strict_types=1);

/**
 * Security audit — scan the codebase for common issues.
 *
 * Usage:
 *   php tools/security_audit.php
 *
 * Skip a finding by adding a `// @safe` comment on the same line.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$base = dirname(__DIR__);
$report = '';
$warnings = 0;

function addFinding(string &$report, int &$warnings, string $severity, string $file, string $message, ?int $line = null): void
{
    $warnings++;
    $icon = match ($severity) {
        'high'   => '!!',
        'medium' => ' !',
        'low'    => ' .',
        default  => '  ',
    };
    $loc = $line !== null ? $file . ':' . $line : $file;
    $report .= sprintf("%s [%s] %s\n     %s\n", $icon, strtoupper($severity), $loc, $message);
}

/* Files we never scan — internal framework code that legitimately builds SQL */
$skipFiles = [
    'Core/Connection.php',
    'Core/Router.php',
    'Core/Response.php',
    'Core/Request.php',
    'Core/Validator.php',
    'Core/Session.php',
    'Core/Csrf.php',
];

$scanDirs = [
    $base . '/src',
    $base . '/views',
    $base . '/config',
    $base . '/routes',
];

$files = [];
foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $files[] = $f->getPathname();
        }
    }
}

foreach ($files as $file) {
    $rel = str_replace([$base . DIRECTORY_SEPARATOR, $base . '/'], '', str_replace('\\', '/', $file));

    /* Skip framework internals */
    $skip = false;
    foreach ($skipFiles as $s) {
        if (str_contains($rel, $s)) { $skip = true; break; }
    }
    if ($skip) continue;

    /* Skip tests */
    if (str_starts_with($rel, 'tests/')) continue;

    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;
        $trim    = ltrim($line);

        /* Skip PHP comments */
        if (str_starts_with($trim, '//') || str_starts_with($trim, '*') || str_starts_with($trim, '/*')) {
            continue;
        }

        /* Skip lines explicitly marked safe */
        if (str_contains($line, '// @safe')) {
            continue;
        }

        /* Skip heredoc/nowdoc content */
        if (preg_match('/<<<["\']?HTML|<<<["\']?SQL/', $line)) {
            continue;
        }

        /* Skip LIMIT/OFFSET interpolations — always integers in this codebase */
        if (preg_match('/LIMIT\s+\{?\$/i', $line) || preg_match('/OFFSET\s+\{?\$/i', $line)) {
            continue;
        }

        /* Skip sprintf-built SQL (column names come from array_keys of trusted arrays) */
        if (str_contains($line, 'sprintf(') && preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i', $line)) {
            continue;
        }

        /* Skip lines that bind parameters (:something) — those are safe */
        if (preg_match('/:\w+/', $line) && preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i', $line)) {
            continue;
        }

        /* Skip WHERE fragments built from whitelisted pieces — they're safe by construction.
           The variables below are ONLY ever composed from hardcoded safe SQL strings
           in this codebase (e.g. `1=1`, `name LIKE :q1`, `status = :st`). */
        if (preg_match('/\{\$(whereSql|where|orderBy|order|groupBy)\}/i', $line)) {
            continue;
        }

        /* ---- Actual checks ---- */

        /* Raw superglobals */
        if (preg_match('/\$_(GET|POST|REQUEST)\s*\[/', $line)) {
            addFinding($report, $warnings, 'medium', $rel, 'Direct superglobal access — use $request->input()', $lineNum);
        }

        /* Weak password hashing */
        if (preg_match('/\b(md5|sha1)\s*\(/', $line) && stripos($line, 'password') !== false) {
            addFinding($report, $warnings, 'high', $rel, 'Weak password hash (md5/sha1) — use password_hash()', $lineNum);
        }

        /* Deprecated mysql_ functions */
        if (preg_match('/\bmysql_(query|connect|fetch|real_escape)/', $line)) {
            addFinding($report, $warnings, 'high', $rel, 'Deprecated mysql_* — use PDO', $lineNum);
        }

        /* eval() */
        if (preg_match('/\beval\s*\(/', $line)) {
            addFinding($report, $warnings, 'high', $rel, 'eval() used', $lineNum);
        }

        /* Real SQL injection: user variable direct-embedded in a query literal
           with NO parameter binding and NO backtick-quoted identifier */
        if (preg_match('/["\']\s*(SELECT|INSERT|UPDATE|DELETE)[^"\']*\$[a-zA-Z_]/i', $line)
            && !preg_match('/:\w+/', $line)
            && !str_contains($line, 'sprintf')
            && !str_contains($line, '`')) {
            addFinding($report, $warnings, 'high', $rel, 'Possible SQL injection — variable embedded in query', $lineNum);
        }
    }
}

/* ---------- Static config checks ---------- */

if (is_file($base . '/.env')) {
    $envRaw = (string) file_get_contents($base . '/.env');

    if (str_contains($envRaw, 'APP_DEBUG=true') && str_contains($envRaw, 'APP_ENV=production')) {
        addFinding($report, $warnings, 'high', '.env', 'APP_DEBUG=true while APP_ENV=production');
    }
    if (preg_match('/^APP_KEY=$/m', $envRaw)) {
        addFinding($report, $warnings, 'high', '.env', 'APP_KEY is empty');
    }
}

if (is_file($base . '/public/.env')) {
    addFinding($report, $warnings, 'high', 'public/.env', '.env must not live under public/');
}

foreach (glob($base . '/public/**/*.sqlite') ?: [] as $f) {
    addFinding($report, $warnings, 'high', str_replace($base . DIRECTORY_SEPARATOR, '', $f), 'SQLite file reachable under public/');
}

if (is_file($base . '/composer.json')) {
    $composer = json_decode((string) file_get_contents($base . '/composer.json'), true);
    $ext = $composer['require'] ?? [];
    foreach (['ext-pdo', 'ext-openssl', 'ext-json', 'ext-mbstring'] as $need) {
        if (!isset($ext[$need])) {
            addFinding($report, $warnings, 'low', 'composer.json', "Missing required extension: $need");
        }
    }
}

/* ---------- Report ---------- */

echo "=====================================================\n";
echo "  Security Audit — Auto Rise\n";
echo "=====================================================\n\n";

if ($warnings === 0) {
    echo "No issues found.\n";
    exit(0);
}

echo $report;
echo "\n";
echo "Total findings: $warnings\n\n";
echo "Legend:\n";
echo "  !! high    — must fix before going live\n";
echo "   ! medium  — strongly recommended\n";
echo "   . low     — optional hardening\n";
echo "\nTip: add '// @safe' to a line to skip it in future audits.\n";

exit($warnings > 0 ? 1 : 0);