<?php

namespace ManaPHP\Socket;

use ManaPHP\Di\FactoryInterface;
use ManaPHP\Socket\Server\Adapter\Swoole;

class ServerFactory implements FactoryInterface
{
    public function make($container, $name, $parameters = [])
    {
        return $container->get(Swoole::class);
    }
}