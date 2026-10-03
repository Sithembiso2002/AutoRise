<?php
declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;

if (!function_exists('base_path')) {
    function base_path(string $p = ''): string { return App::basePath($p); }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed { return Config::get($key, $default); }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($v === false || $v === null || $v === '') return $default;
        return match (strtolower((string) $v)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $v,
        };
    }
}

if (!function_exists('db')) {
    function db(?string $name = null): \App\Core\Connection { return Database::connection($name); }
}

if (!function_exists('e')) {
    function e(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim((string) env('APP_URL', ''), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        $file = base_path('views/' . str_replace('.', '/', $name) . '.php');
        if (!is_file($file)) {
            throw new \RuntimeException("View [{$name}] not found at {$file}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}

if (!function_exists('auth')) {
    function auth(): ?array { return Auth::user(); }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string { return Csrf::token(); }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string { return Csrf::field(); }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flashes')) {
    function flashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::pull('_old', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('remember_old')) {
    function remember_old(array $data): void
    {
        Session::put('_old', $data);
    }
}

if (!function_exists('intended')) {
    function intended(string $default = '/'): string
    {
        return (string) Session::pull('_intended', $default);
    }
}

if (!function_exists('product_image')) {
    function product_image(?string $path, string $fallbackText = 'Product'): string
    {
        if (!$path) {
            return 'https://via.placeholder.com/600x400/1b1b1b/e60000?text=' . urlencode($fallbackText);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $clean = str_replace('\\', '/', trim($path));
        $clean = ltrim($clean, '/');
        $filename = basename($clean);

        $candidates = [
            'assets/' . $clean,
            'assets/uploads/' . $clean,
            'assets/uploads/product_images/' . $filename,
            'assets/img/' . $filename,
            'assets/uploads/' . $filename,
        ];

        foreach ($candidates as $rel) {
            $full = base_path('public/' . $rel);
            if (is_file($full)) {
                return url($rel);
            }
        }

        return url('assets/img/' . $filename);
    }
}

if (!function_exists('user_avatar')) {
    function user_avatar(?array $user): ?string
    {
        if (!$user) return null;
        $p = $user['avatar'] ?? null;
        if (!$p) return null;

        $clean = str_replace('\\', '/', trim((string) $p));
        $clean = ltrim($clean, '/');

        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
            return $clean;
        }
        if (is_file(base_path('public/' . $clean))) {
            return url($clean);
        }
        return null;
    }
}

if (!function_exists('initials_of')) {
    function initials_of(?string $name, string $fallback = 'U'): string
    {
        $name = trim((string) $name);
        if ($name === '') return $fallback;

        $parts = preg_split('/\s+/', $name) ?: [];
        $out = '';
        foreach ($parts as $p) {
            if ($p !== '') $out .= mb_strtoupper(mb_substr($p, 0, 1));
            if (mb_strlen($out) >= 2) break;
        }
        return $out !== '' ? $out : $fallback;
    }
}

if (!function_exists('brand_name')) {
    function brand_name(): string
    {
        return (string) env('APP_NAME', 'Auto Rise');
    }
}

if (!function_exists('brand_logo')) {
    function brand_logo(): ?string
    {
        $rel = 'assets/img/logo.png';
        if (is_file(base_path('public/' . $rel))) {
            return url($rel);
        }
        return null;
    }
}
