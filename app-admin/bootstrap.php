<?php

use ManaPHP\Di\Container;
use ManaPHP\Kernel;

ini_set('memory_limit', -1);
ini_set('html_errors', 'off');
ini_set('default_socket_timeout', -1);

require __DIR__ . '/vendor/autoload.php';

function bootstrap(string $server): void
{
    $container = new Container([Kernel::class => ['rootDir' => __DIR__]]);
    $container->get(Kernel::class)->start($server);
}