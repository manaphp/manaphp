#!/usr/bin/env php
<?php

use ManaPHP\Kernel;

ini_set('memory_limit', -1);
ini_set('html_errors', 'off');
ini_set('default_socket_timeout', -1);

require __DIR__ . '/vendor/autoload.php';

$kernel = new Kernel(__DIR__);
$kernel->start('ManaPHP\Cli\ServerInterface');