#!/usr/bin/env php
<?php
error_reporting(E_ALL);

require __DIR__ . '/../../ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader();

require __DIR__ . '/app/Application.php';

$cli = new \App\Cli\Application($loader);

$cli->main();