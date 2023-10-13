<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Redis\Connection;

#[Verbosity(Verbosity::LOW)]
class RedisCalling
{
    public function __construct(
        public Connection $redis,
        public string $method,
        public array $arguments
    ) {

    }
}