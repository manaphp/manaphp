<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;

class ServerFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
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