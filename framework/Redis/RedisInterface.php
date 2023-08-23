<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

/**
 * @mixin \Redis
 */
interface RedisInterface extends RedisDbInterface, RedisCacheInterface, RedisBrokerInterface
{
}