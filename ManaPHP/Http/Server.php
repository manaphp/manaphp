<?php

namespace ManaPHP\Http;

(static function () {
    if (PHP_SAPI === 'cli') {
        if (class_exists('Workerman\Worker')) {
            $class = 'ManaPHP\Http\Server\Adapter\Workerman';
        } elseif (extension_loaded('swoole')) {
            $class = 'ManaPHP\Http\Server\Adapter\Swoole';
        } else {
            $class = 'ManaPHP\Http\Server\Adapter\Php';
        }
    } elseif (PHP_SAPI === 'cli-server') {
        $class = 'ManaPHP\Http\Server\Adapter\Php';
    } else {
        $class = 'ManaPHP\Http\Server\Adapter\Fpm';
    }
    class_alias($class, 'ManaPHP\Http\Server');
})();