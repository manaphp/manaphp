<?php

ini_set('html_errors', 'on');

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader();

require ROOT_PATH . '/Application/SwooleHttpServer.php';
$application = new \Application\SwooleHttpServer($loader);

$application->main();