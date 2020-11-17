#!/usr/bin/env php
<?php

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../../ManaPHP/Loader.php';

$loader = new \ManaPHP\Loader();
$app = new \ManaPHP\Cli\Application($loader);
$app->main();