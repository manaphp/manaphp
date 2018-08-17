#!/usr/bin/env php
<?php
/** @noinspection PhpIncludeInspection */
is_file(__DIR__ . '/vendor/autoload.php') && require __DIR__ . '/vendor/autoload.php';
/** @noinspection PhpIncludeInspection */
require __DIR__ . (is_dir(__DIR__ . '/vendor/manaphp/framework') ? '/vendor/manaphp/framework' : '/../../ManaPHP') . '/Loader.php';

$loader = new \ManaPHP\Loader();
require __DIR__ . '/app/Application.php';
$cli = new \App\Cli\Application($loader);
$cli->main();