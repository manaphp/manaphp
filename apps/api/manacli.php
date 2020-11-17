#!/usr/bin/env php
<?php

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../../ManaPHP/Loader.php';

$loader = new \ManaPHP\Loader();

require __DIR__ . '/app/Application.php';
$app = new App\Application($loader);
$app->cli();