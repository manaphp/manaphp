#!/usr/bin/env php
<?php
error_reporting(E_ALL);
ini_set('html_errors', 'off');

require __DIR__ . '/ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader();

require __DIR__ . '/Application/Cli.php';
$cli = new \Application\Cli($loader);

$cli->main();