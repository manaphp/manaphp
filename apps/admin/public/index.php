<?php
error_reporting(E_ALL);
ini_set('html_errors', 'on');

define('ROOT_PATH', __DIR__);

if (PHP_EOL === "\n") {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../ManaPHP/Loader.php';
}

$loader = new \ManaPHP\Loader();
require __DIR__.'/../app/Application.php';
$app = new \App\Admin\Application($loader);
$app->main();