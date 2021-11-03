<?php

namespace ManaPHP\Messaging;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Messaging\PubSub\Adapter\Redis;

class PubSubFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(Redis::class);
    }
}