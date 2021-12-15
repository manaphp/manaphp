<?php

namespace ManaPHP\Caching;

use ManaPHP\Di\FactoryInterface;

class CacheFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        if (extension_loaded('redis')) {
            $class = 'ManaPHP\Caching\Cache\Adapter\Redis';
        } else {
            $class = 'ManaPHP\Caching\Cache\Adapter\File';
        }

        return $container->make($class, $parameters);
    }
}