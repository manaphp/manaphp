<?php

ini_set('memory_limit', -1);

/** @noinspection PhpIncludeInspection */
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/vendor/manaphp/framework/Loader.php';
}

$loader = new \ManaPHP\Loader();

require __DIR__ . '/app/Swoole.php';
$app = new \App\Swoole($loader);
$app->main();