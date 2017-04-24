<?php

$_SERVER['SERVER_ADDR'] = '0.0.0.0';
$_SERVER['SERVER_PORT'] = '1983';

$_SERVER['REQUEST_SCHEME'] = 'http';

if (PHP_SAPI === 'cli') {
    echo 'server listen on: ' . $_SERVER['SERVER_ADDR'], ':', $_SERVER['SERVER_PORT'], PHP_EOL;

    if (DIRECTORY_SEPARATOR === '\\') {
        $r = `explorer.exe $_SERVER[REQUEST_SCHEME]://localhost:$_SERVER[SERVER_PORT]/`;
    }

    chdir(__DIR__);

    $r = `php -S $_SERVER[SERVER_ADDR]:$_SERVER[SERVER_PORT] -t Public server.php`;
    return $r;
}
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a ManaPHP
// application without having installed a "real" web server software here.
if ($uri !== '/') {
    if (file_exists(__DIR__ . '/Public/' . $uri)
        || preg_match('#(.css|.js|.gif|.png|.jpg|.jpeg|.ttf|.woff|.ico)$#', $uri) === 1
    ) {
        return false;
    }
}

$_GET['_url'] = $uri;
require_once __DIR__ . '/Public/index.php';
