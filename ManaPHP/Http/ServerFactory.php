<?php

namespace ManaPHP\Http;

use ManaPHP\Di\FactoryInterface;

class ServerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
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

        return $container->get($class);
    }
}