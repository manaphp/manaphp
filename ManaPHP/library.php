<?php

use ManaPHP\Loader;

require_once __DIR__ . '/Loader.php';
$loader = new Loader();

defined('MANAPHP_CLI') or define('MANAPHP_CLI', $_SERVER['DOCUMENT_ROOT'] === '');

$app = new ManaPHP\Application($loader);
$app->main();