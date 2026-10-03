<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Database
{
    /** @var array<string, Connection> */
    private static array $connections = [];

    /**
     * Get a connection by name (default from config).
     *
     * If the primary connection fails and `database.fallback.enabled`
     * is true, transparently connects to the fallback instead.
     */
    public static function connection(?string $name = null): Connection
    {
        $name ??= (string) (config('database.default') ?? 'mysql');

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $config = config("database.connections.$name");
        if (!$config) {
            throw new \RuntimeException("Database connection [{$name}] is not configured.");
        }

        try {
            return self::$connections[$name] = new Connection($config);
        } catch (Throwable $e) {
            $fallbackEnabled = (bool) config('database.fallback.enabled', false);
            $fallbackName    = (string) (config('database.fallback.connection') ?? 'sqlite');

            if ($fallbackEnabled && $fallbackName !== $name) {
                try {
                    Logger::warning('Primary DB connection failed, using fallback', [
                        'primary'  => $name,
                        'fallback' => $fallbackName,
                        'error'    => $e->getMessage(),
                    ]);
                } catch (Throwable) {
                    // ignore logger failure
                }

                $fbConfig = config("database.connections.$fallbackName");
                if ($fbConfig) {
                    return self::$connections[$name] = new Connection($fbConfig);
                }
            }

            throw $e;
        }
    }

    /**
     * Get the SQLite mirror regardless of primary connection.
     * Useful for tools/reports that should always read from the mirror.
     */
    public static function mirror(): Connection
    {
        $name   = (string) (config('database.fallback.connection') ?? 'sqlite');
        $config = config("database.connections.$name");

        if (!$config) {
            throw new \RuntimeException("Mirror connection [{$name}] is not configured.");
        }

        $key = '_mirror_' . $name;
        return self::$connections[$key] ??= new Connection($config);
    }

    /**
     * Check if the primary database is reachable right now.
     */
    public static function isPrimaryHealthy(): bool
    {
        try {
            self::connection()->scalar('SELECT 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Forget cached connections. Useful in tests and long-running processes.
     */
    public static function reset(): void
    {
        self::$connections = [];
    }
}