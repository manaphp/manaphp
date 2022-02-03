<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;

class RedisCacheFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(RedisInterface::class);
    }
}