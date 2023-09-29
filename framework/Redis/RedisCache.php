<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use Psr\Container\ContainerInterface;

class RedisCache
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        if ($parameters === []) {
            if (str_contains($id, '#')) {
                list(, $anchor) = explode($id, '#', 2);
                return $this->container->get(RedisInterface::class . '#' . $anchor);
            } elseif ($this->container->has(RedisInterface::class . '#cache')) {
                return $this->container->get(RedisInterface::class . '#cache');
            } else {
                return $this->container->get(RedisInterface::class);
            }
        } else {
            return $this->maker->make(Redis::class, $parameters, $id);
        }
    }
}