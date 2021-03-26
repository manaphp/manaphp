<?php

ini_set('memory_limit', -1);

/** @noinspection PhpIncludeInspection */
if (is_file(dirname(__DIR__) . '/vendor/manaphp/framework/Loader.php')) {
    include dirname(__DIR__) . '/vendor/manaphp/framework/Loader.php';
} else {
    include __DIR__ . '/../../ManaPHP/Loader.php';
}

$loader = new \ManaPHP\Loader();
require dirname(__DIR__) . '/app/Application.php';
$app = new \App\Application($loader);
$app->main();