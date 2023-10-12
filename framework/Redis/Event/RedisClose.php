<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Redis\Connection;
use Redis;
use RedisCluster;

#[Verbosity(Verbosity::HIGH)]
class RedisClose
{
    public function __construct(
        public Connection $connection,
        public string $uri,
        public Redis|RedisCluster $redis,
    ) {

    }
}