#!/usr/bin/env php

<?php
error_reporting(E_ALL);
ini_set('html_errors', 'off');

require __DIR__ . '/ManaPHP/Autoloader.php';
new \ManaPHP\Autoloader(__DIR__);

require __DIR__ . '/Application/Cli.php';
$cli = new \Application\Cli();

$cli->main();