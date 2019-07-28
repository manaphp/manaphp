<?php

use ManaPHP\Loader;

require_once __DIR__ . '/Loader.php';
$loader = new Loader();
$app = new ManaPHP\Application($loader);
$app->main();