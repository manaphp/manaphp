<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Messaging\Queue\Adapter\Redis;

class QueueFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(Redis::class);
    }
}