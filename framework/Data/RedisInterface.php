<?php
declare(strict_types=1);

namespace ManaPHP\Data;

/**
 * @mixin \Redis
 */
interface RedisInterface
{
    public function call(string $method, array $arguments): mixed;
}