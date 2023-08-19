<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

interface RedisConnectorInterface
{
    public function get(string $name = 'default'): Redis;
}