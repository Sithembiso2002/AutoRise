<?php
declare(strict_types=1);

namespace App\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;

final class Logger
{
    private static ?MonologLogger $logger = null;

    private static function boot(): MonologLogger
    {
        if (self::$logger instanceof MonologLogger) {
            return self::$logger;
        }

        $logger = new MonologLogger('app');

        // Rotating file: one file per day, keep 14 days
        $logDir = base_path('storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $handler = new RotatingFileHandler(
            $logDir . '/app.log',
            14,
            Level::Debug,
            true,
            0664
        );

        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        // In local environment also print to stderr so dev sees errors immediately
        if (env('APP_DEBUG')) {
            $stderr = new StreamHandler('php://stderr', Level::Error);
            $stderr->setFormatter($formatter);
            $logger->pushHandler($stderr);
        }

        return self::$logger = $logger;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::boot()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::boot()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::boot()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::boot()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::boot()->critical($message, $context);
    }

    /**
     * Log a caught exception with full context.
     */
    public static function exception(\Throwable $e, array $extra = []): void
    {
        self::boot()->error(
            get_class($e) . ': ' . $e->getMessage(),
            array_merge([
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url'   => $_SERVER['REQUEST_URI'] ?? null,
                'ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
                'user'  => Session::get('user')['id'] ?? null,
            ], $extra)
        );
    }
}