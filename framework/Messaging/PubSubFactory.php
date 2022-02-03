<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Messaging\PubSub\Adapter\Redis;
use ManaPHP\Di\ContainerInterface;

class PubSubFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(Redis::class);
    }
}