#!/usr/bin/env php
<?php
error_reporting(E_ALL);

require __DIR__ . '/ManaPHP/Loader.php';
$loader = new \ManaPHP\Loader();

foreach ([__DIR__, getcwd()] as $dir) {
    if (is_dir($dir . '/Application')) {
        $appDir = $dir . '/Application';
        break;
    } elseif (is_file($dir . '/Cli.php')) {
        $appDir = $dir;
        break;
    }
}

if (!isset($appDir)) {
    $appDir = getcwd();
}

$loader->registerNamespaces(['Application' => $appDir]);

if (is_file($appDir . '/Cli.php')) {
    require $appDir . '/Cli.php';
    $cli = new \Application\Cli($loader);
} else {
    $cli = new \ManaPHP\Cli\Application($loader);
}

$cli->main();