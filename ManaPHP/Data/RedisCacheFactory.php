<?php

namespace ManaPHP\Data;

use ManaPHP\Di\FactoryInterface;

class RedisCacheFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(RedisInterface::class);
    }
}