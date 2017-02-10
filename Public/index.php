<?php
error_reporting(E_ALL);
ini_set('html_errors', 'on');

date_default_timezone_set('PRC');

define('ROOT_PATH', dirname(__DIR__));

class_exists('ManaPHP\Loader') || require ROOT_PATH . '/ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader(ROOT_PATH . '/ManaPHP');

require ROOT_PATH . '/Application/Application.php';
$application = new \Application\Application($loader);

$application->main();