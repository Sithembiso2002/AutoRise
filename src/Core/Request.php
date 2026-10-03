<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private function __construct(
        private array $query,
        private array $body,
        private array $files,
        private array $server,
    ) {}

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER);
    }

    public function method(): string
    {
        $m = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($m === 'POST' && isset($this->body['_method'])) {
            $m = strtoupper((string) $this->body['_method']);
        }
        return $m;
    }

    public function path(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Strip APP_URL base path if we're served from a subfolder
        $basePath = parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? '';
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function all(): array { return $this->body + $this->query; }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isPost(): bool { return $this->method() === 'POST'; }
    public function isGet(): bool  { return $this->method() === 'GET'; }
}