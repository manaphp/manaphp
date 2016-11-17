<?php
error_reporting(E_ALL);
ini_set('html_errors', 'on');

class_exists('ManaPHP\Loader') || require dirname(__DIR__) . '/ManaPHP/Loader.php';
//require dirname(__DIR__) . '/ManaPHP/base.php';
$loader = new \ManaPHP\Loader(dirname(__DIR__).'/ManaPHP');
//new \ManaPHP\Mvc\Router\RewriteChecker();

require dirname(__DIR__) . '/Application/Application.php';
$application = new \Application\Application($loader);

$application->main();
//$application->getDependencyInjector()->filesystem->filePut('@manaphp/base.php',(new \ManaPHP\Loader\ClassMerger())->merge());