<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(array $opts = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_name($opts['name'] ?? 'app_session');
        session_set_cookie_params([
            'lifetime' => $opts['lifetime'] ?? 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool) ($opts['secure']   ?? false),
            'httponly' => (bool) ($opts['httponly'] ?? true),
            'samesite' => $opts['samesite'] ?? 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) ($opts['lifetime'] ?? 7200));

        session_start();

        // Absolute + idle timeout
        $now = time();
        if (!isset($_SESSION['_created'])) $_SESSION['_created'] = $now;

        $absoluteExpired = ($now - (int) $_SESSION['_created']) > 7200;
        $idleExpired     = ($now - (int) ($_SESSION['_last'] ?? $now)) > 1800;

        if ($absoluteExpired || $idleExpired) {
            self::destroy();
            session_start();
            $_SESSION['_created'] = $now;
        }

        $_SESSION['_last'] = $now;

        // User-agent fingerprint binding
        $fp = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (!isset($_SESSION['_fp'])) {
            $_SESSION['_fp'] = $fp;
        } elseif (!hash_equals($_SESSION['_fp'], $fp)) {
            self::destroy();
            session_start();
        }

        // Periodic rotation
        if (random_int(1, 100) === 1) {
            session_regenerate_id(true);
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
    }

    public static function get(string $k, mixed $d = null): mixed
    {
        return $_SESSION[$k] ?? $d;
    }

    public static function put(string $k, mixed $v): void
    {
        $_SESSION[$k] = $v;
    }

    public static function forget(string $k): void
    {
        unset($_SESSION[$k]);
    }

    public static function pull(string $k, mixed $d = null): mixed
    {
        $v = $_SESSION[$k] ?? $d;
        unset($_SESSION[$k]);
        return $v;
    }
}