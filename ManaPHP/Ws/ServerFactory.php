<?php

namespace ManaPHP\Ws;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Ws\Server\Adapter\Swoole;

class ServerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(Swoole::class);
    }
}