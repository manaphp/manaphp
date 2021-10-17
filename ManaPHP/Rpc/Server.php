<?php

namespace ManaPHP\Rpc;

(static function () {
    if (PHP_SAPI === 'cli') {
        if (extension_loaded('swoole')) {
            $class = 'ManaPHP\Rpc\Http\Server\Adapter\Swoole';
        } else {
            $class = 'ManaPHP\Rpc\Http\Server\Adapter\Php';
        }
    } elseif (PHP_SAPI === 'cli-server') {
        $class = 'ManaPHP\Rpc\Http\Server\Adapter\Php';
    } else {
        $class = 'ManaPHP\Rpc\Http\Server\Adapter\Fpm';
    }
    class_alias($class, 'ManaPHP\Rpc\Server');
})();