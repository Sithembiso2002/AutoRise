<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\CspMiddleware;
use Dotenv\Dotenv;
use Throwable;

final class App
{
    private static string $basePath;
    private Router $router;

    public function __construct(string $basePath)
    {
        self::$basePath = rtrim($basePath, '/\\');
        $this->router   = new Router();
    }

    public static function basePath(string $path = ''): string
    {
        return self::$basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function router(): Router { return $this->router; }

    public function run(): void
    {
        // 1. Load environment
        Dotenv::createImmutable(self::basePath())->safeLoad();

        // 2. Debug mode
        if (env('APP_DEBUG')) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }

        // 3. Load config from /config/*.php
        Config::load(self::basePath());

        // 4. Register global error/exception/shutdown handlers
        $this->registerHandlers();

        // 5. Start session
        Session::start([
            'name'     => 'buycar_session',
            'lifetime' => (int) env('SESSION_LIFETIME', 120) * 60,
            'secure'   => str_starts_with((string) env('APP_URL', ''), 'https'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // 6. Register routes
        (require self::basePath('routes/web.php'))($this->router);

        // 7. Dispatch request
        $request = Request::capture();

        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->handleException($e);
        }

        // 8. Global hardening headers (CSP, X-Frame-Options, etc.) on every response
        try {
            $response = (new CspMiddleware())->handle($request, fn() => $response);
        } catch (Throwable) {
            // never break the response chain on header failure
        }

        // 9. Send
        $response->send();
    }

    /**
     * Register PHP error handler + shutdown handler.
     * In debug mode, PHP warnings/notices become exceptions so we see them
     * immediately in the trace.
     */
    private function registerHandlers(): void
    {
        if (env('APP_DEBUG')) {
            set_error_handler(function ($severity, $message, $file, $line) {
                if (!(error_reporting() & $severity)) {
                    return false;
                }
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });
        }

        // Catch fatals that bypass try/catch
        register_shutdown_function(function () {
            $err = error_get_last();

            if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                try {
                    Logger::critical('Fatal error', [
                        'message' => $err['message'],
                        'file'    => $err['file'],
                        'line'    => $err['line'],
                        'url'     => $_SERVER['REQUEST_URI'] ?? null,
                        'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                    ]);
                } catch (Throwable) {
                    // never break on logging failure
                }
            }
        });
    }

    /**
     * Turn a thrown exception into an HTTP Response.
     * Always logs. In debug mode returns the full trace.
     */
    private function handleException(Throwable $e): Response
    {
        try {
            Logger::exception($e);
        } catch (Throwable) {
            // ignore logger failure — never break on logging
        }

        if (env('APP_DEBUG')) {
            $msg = htmlspecialchars(
                get_class($e) . ': ' . $e->getMessage()
                . "\n\n" . $e->getFile() . ':' . $e->getLine()
                . "\n\n" . $e->getTraceAsString(),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            return Response::html(
                '<pre style="background:#111;color:#f88;padding:20px;font:14px/1.4 monospace;white-space:pre-wrap">'
                . $msg . '</pre>',
                500
            );
        }

        return Response::error(500);
    }
}