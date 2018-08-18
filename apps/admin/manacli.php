#!/usr/bin/env php
<?php
chdir(__DIR__);

/** @noinspection PhpIncludeInspection */
is_file('vendor/autoload.php') && require 'vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require (is_file('vendor/manaphp/framework/Loader.php') ? 'vendor/manaphp/framework' : '../../ManaPHP') . '/Loader.php';

$loader = new \ManaPHP\Loader();
$cli = new \ManaPHP\Cli\Application($loader);
$cli->main();