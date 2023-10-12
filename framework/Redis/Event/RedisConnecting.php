<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Redis\Connection;

#[Verbosity(Verbosity::MEDIUM)]
class RedisConnecting
{
    public function __construct(
        public Connection $connection,
        public string $uri,
    ) {

    }
}