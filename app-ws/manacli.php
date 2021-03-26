#!/usr/bin/env php
<?php

/** @noinspection PhpIncludeInspection */
if (is_file(__DIR__ . '/vendor/manaphp/framework/Loader.php')) {
    require __DIR__ . '/vendor/manaphp/framework/Loader.php';
} else {
    require __DIR__ . '/../ManaPHP/Loader.php';
}

$loader = new \ManaPHP\Loader();

require __DIR__ . '/app/Application.php';
$app = new App\Application($loader);
$app->cli();