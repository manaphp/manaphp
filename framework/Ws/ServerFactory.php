<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\ContainerInterface;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Ws\Server\Adapter\Swoole;

class ServerFactory implements FactoryInterface
{
    public function make(ContainerInterface $container, string $name, array $parameters = []): object
    {
        return $container->get(Swoole::class);
    }
}