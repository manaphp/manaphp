<?php

use ManaPHP\Kernel;

ini_set('memory_limit', -1);
ini_set('html_errors', 'off');
ini_set('default_socket_timeout', -1);

require dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel(dirname(__FILE__, 2));
$kernel->start('ManaPHP\Http\ServerInterface');