<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Data\Redis\Connection;
use ManaPHP\Pool\Transientable;

/**
 * @mixin \Redis
 */
interface RedisInterface extends Transientable
{
    public function call(string $method, array $arguments, ?Connection $connection = null): mixed;
}