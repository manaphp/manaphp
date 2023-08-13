<?php

use ManaPHP\Di\Container;
use ManaPHP\Kernel;

ini_set('memory_limit', -1);
ini_set('html_errors', 'off');
ini_set('default_socket_timeout', -1);

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container([Kernel::class => ['rootDir' => dirname(__FILE__, 2)]]);
$container->get(Kernel::class)->start('ManaPHP\Http\ServerInterface');