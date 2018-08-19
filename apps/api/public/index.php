<?php
chdir(dirname(__DIR__));

/** @noinspection PhpIncludeInspection */
is_file('vendor/autoload.php') && require 'vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require (is_dir('vendor/manaphp/framework') ? 'vendor/manaphp/framework' : '../../ManaPHP') . '/Loader.php';

$loader = new \ManaPHP\Loader();
require dirname(__DIR__) . '/app/Application.php';
$app = new \App\Application($loader);

//$app = new \ManaPHP\Swoole\Http\Server\Application($loader);
$app->main();