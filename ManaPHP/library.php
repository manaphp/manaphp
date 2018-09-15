<?php

require_once __DIR__ . '/Loader.php';
$loader = new \ManaPHP\Loader();
$app = new ManaPHP\Application($loader);
$app->main();