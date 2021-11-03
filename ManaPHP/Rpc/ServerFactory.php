<?php

namespace ManaPHP\Rpc;

use ManaPHP\Di\FactoryInterface;

class ServerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
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

        return $container->get($class);
    }
}