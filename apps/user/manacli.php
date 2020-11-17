#!/usr/bin/env php
<?php

/** @noinspection PhpIncludeInspection */
if (is_file(__DIR__ . '/vendor/manaphp/framework/Loader.php')) {
    require __DIR__ . '/vendor/manaphp/framework/Loader.php';
} else {
    require __DIR__ . '/../../ManaPHP/Loader.php';
}

$loader = new \ManaPHP\Loader();
$app = new \ManaPHP\Cli\Application($loader);
$app->main();