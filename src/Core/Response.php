<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = [],
    ) {}

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    public static function notFound(string $message = 'The page you requested does not exist.'): self
    {
        return self::error(404, $message);
    }

    /**
     * Render an error response.
     *
     * Loads the styled view from views/errors/{status}.php if it exists,
     * falls back to views/errors/500.php, then to a minimal inline page.
     */
    public static function error(int $status, string $message = ''): self
    {
        $candidates = [$status, 500];

        foreach ($candidates as $code) {
            $path = base_path('views/errors/' . $code . '.php');

            if (is_file($path)) {
                ob_start();
                // Expose $message inside the view
                require $path;
                $html = (string) ob_get_clean();
                return self::html($html, $status);
            }
        }

        // Fallback: minimal styled page (rarely reached)
        $safe = htmlspecialchars(
            $message !== '' ? $message : 'Something went wrong.',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $html = '<!doctype html><meta charset="utf-8">'
              . '<title>' . $status . ' | ' . e(brand_name()) . '</title>'
              . '<style>'
              . 'body{font-family:system-ui,sans-serif;background:#111;color:#eee;'
              . 'display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:24px}'
              . '.box{text-align:center;max-width:480px;padding:32px}'
              . 'h1{font-size:72px;margin:0;color:#e60000}'
              . 'p{color:#bbb;line-height:1.6}'
              . 'a{color:#e60000;text-decoration:none}'
              . '</style>'
              . '<div class="box">'
              . '<h1>' . $status . '</h1>'
              . '<p>' . $safe . '</p>'
              . '<p><a href="/">&larr; Back to home</a></p>'
              . '</div>';

        return self::html($html, $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->headers[$k] = $v;
        }
        return $this;
    }

    public function status(): int      { return $this->status; }
    public function content(): string  { return $this->content; }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }

            // Global security headers (safe defaults for a public site)
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }

        echo $this->content;
    }
}