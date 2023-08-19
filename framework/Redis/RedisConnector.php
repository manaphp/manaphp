<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Inject;
use Psr\Container\ContainerInterface;

class RedisConnector implements RedisConnectorInterface
{
    #[Inject] protected ContainerInterface $container;

    public function get(string $name = 'default'): Redis
    {
        return $this->container->get(RedisInterface::class . ($name === 'default' ? '' : "#$name"));
    }
}