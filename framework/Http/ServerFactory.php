<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;

class ServerFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
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

        $type = lcfirst(substr($class, strrpos($class, '\\') + 1));
        $id = "ManaPHP\Http\ServerInterface#$type";

        return $container->get($container->has($id) ? $id : $class);
    }
}