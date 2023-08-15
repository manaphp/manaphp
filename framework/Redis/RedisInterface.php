<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

/**
 * @mixin \Redis
 */
interface RedisInterface
{
    public function call(string $method, array $arguments): mixed;
}