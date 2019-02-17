#!/usr/bin/env php
<?php

require __DIR__ . '/../../ManaPHP/Loader.php';

$loader = new \ManaPHP\Loader();
require __DIR__ . '/app/Application.php';
$cli = new \App\Application($loader);
$cli->main();
