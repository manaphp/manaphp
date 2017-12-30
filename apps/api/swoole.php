<?php

ini_set('html_errors', 'on');

require  __DIR__. '/vendor/manaphp/framework/Loader.php';
//require __DIR__.'/../../ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader();

require __DIR__ . '/app/Swoole.php';
$application = new \App\Api\Swoole($loader);

$application->main();