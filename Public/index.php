<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/21
 * Time: 21:52
 */

error_reporting(E_ALL);

require dirname(__DIR__) . '/ManaPHP/Autoloader.php';
\ManaPHP\Autoloader::register(false);
new \ManaPHP\Mvc\Router\RewriteChecker();

require dirname(__DIR__) . '/Application/Application.php';
$application = new \Application\Application();

echo $application->main();