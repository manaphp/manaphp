<?php
declare(strict_types=1);

namespace ManaPHP\Caching;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;

class CacheFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        if (extension_loaded('redis')) {
            $class = 'ManaPHP\Caching\Cache\Adapter\Redis';
        } else {
            $class = 'ManaPHP\Caching\Cache\Adapter\File';
        }

        return $container->make($class, $parameters);
    }
}