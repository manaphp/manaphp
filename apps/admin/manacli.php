#!/usr/bin/env php
<?php
/** @noinspection PhpIncludeInspection */
is_file(__DIR__ . '/vendor/autoload.php') && require __DIR__ . '/vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require __DIR__ . (is_dir(__DIR__ . '/vendor/') ? '/vendor/manaphp/framework' : '/../../ManaPHP') . '/Loader.php';

$loader = new \ManaPHP\Loader();
$cli = new \ManaPHP\Cli\Application($loader);
$cli->main();