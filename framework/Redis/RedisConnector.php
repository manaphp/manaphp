<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Container\ContainerInterface;

class RedisConnector implements RedisConnectorInterface
{
    #[Autowired] protected ContainerInterface $container;

    public function get(string $name = 'default'): Redis
    {
        return $this->container->get(RedisInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}