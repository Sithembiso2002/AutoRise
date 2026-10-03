<?php
declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'mysql' => [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'shop_system'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
        ],

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => base_path(env('SQLITE_PATH', 'database/shop_system.sqlite')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    | If the primary connection cannot be reached and `enabled` is true,
    | Database::connection() silently uses the fallback connection instead.
    |
    | Set DB_FALLBACK=true in .env to activate.
    */
    'fallback' => [
        'enabled'    => (bool) env('DB_FALLBACK', false),
        'connection' => (string) env('DB_FALLBACK_CONNECTION', 'sqlite'),
    ],
];