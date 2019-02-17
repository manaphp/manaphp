#!/usr/bin/env php
<?php

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../../ManaPHP/Loader.php';

$loader = new \ManaPHP\Loader();
$cli = new \ManaPHP\Cli\Application($loader);
$cli->main();