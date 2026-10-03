<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static ?array $items = null;

    public static function load(string $basePath): void
    {
        self::$items = [];
        foreach (glob($basePath . '/config/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$items === null) return $default;

        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) return $default;
            $value = $value[$seg];
        }
        return $value;
    }
}