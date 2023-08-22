<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Redis\Connection;
use Redis;
use RedisCluster;

class RedisClose
{
    public function __construct(
        public Connection $connection,
        public string $uri,
        public Redis|RedisCluster $redis,
    ) {

    }
}