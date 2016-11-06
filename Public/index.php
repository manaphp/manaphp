<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/21
 * Time: 21:52
 */

error_reporting(E_ALL);
ini_set('html_errors', 'on');

require dirname(__DIR__) . '/ManaPHP/Loader.php';
//require dirname(__DIR__) . '/ManaPHP/base.php';
$loader = new \ManaPHP\Loader();
//new \ManaPHP\Mvc\Router\RewriteChecker();

require dirname(__DIR__) . '/Application/Application.php';
$application = new \Application\Application($loader);

$application->main();
//$application->getDependencyInjector()->filesystem->filePut('@manaphp/base.php',(new \ManaPHP\Loader\ClassMerger())->merge());