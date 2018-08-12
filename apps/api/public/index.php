<?php
//require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../../../ManaPHP/Loader.php';
//require dirname(__DIR__).'/vendor/manaphp/framework/Loader.php';

$loader = new \ManaPHP\Loader();
require dirname(__DIR__) . '/app/Application.php';
$app = new \App\Api\Application($loader);
$app->main();