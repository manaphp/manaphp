<?php
chdir(dirname(__DIR__));

/** @noinspection PhpIncludeInspection */
is_file('vendor/autoload.php') && require 'vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require is_file('vendor/manaphp/framework/Loader.php') ? 'vendor/manaphp/framework/Loader.php' : '../../ManaPHP/Loader.php';

$loader = new \ManaPHP\Loader();
require 'app/Application.php';
$app = new \App\Application($loader);
$app->main();