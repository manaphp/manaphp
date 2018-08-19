<?php
chdir(__DIR__);

/** @noinspection PhpIncludeInspection */
is_file('vendor/autoload.php') && require 'vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require 'vendor/manaphp/framework/Loader.php';

$loader = new \ManaPHP\Loader();

require 'app/Swoole.php';
$app = new \App\Swoole($loader);
$app->main();