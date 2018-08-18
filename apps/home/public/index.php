<?php
chdir(dirname(__DIR__));

/** @noinspection PhpIncludeInspection */
is_file('vendor/autoload.php') && require 'vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require (is_dir('vendor/manaphp/framework') ? 'vendor/manaphp/framework' : '../../ManaPHP') . '/Loader.php';

$loader = new \ManaPHP\Loader();
require 'app/Application.php';
$app = new \App\Application($loader);
$app->main();