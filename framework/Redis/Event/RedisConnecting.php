<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Redis\Connection;

class RedisConnecting
{
    public function __construct(
        public Connection $connection,
        public string $uri,
    ) {

    }
}