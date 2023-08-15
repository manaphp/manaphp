<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use Psr\Container\ContainerInterface;

class RedisDbFactory
{
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, string $id)
    {
        if ($parameters === []) {
            if (str_contains($id, '#')) {
                list(, $anchor) = explode($id, '#', 2);
                return $this->container->get(RedisInterface::class . '#' . $anchor);
            } elseif ($this->container->has(RedisInterface::class . '#db')) {
                return $this->container->get(RedisInterface::class . '#db');
            } else {
                return $this->container->get(RedisInterface::class);
            }
        } else {
            return $this->maker->make(Redis::class, $parameters, $id);
        }
    }
}