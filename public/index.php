<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer dependencies not installed. Run: composer install in ' . BASE_PATH);
}

require $autoload;

use App\Core\App;

(new App(BASE_PATH))->run();