<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Redis\Connection;

#[Verbosity(Verbosity::HIGH)]
class RedisCalled
{
    public function __construct(
        public Connection $redis,
        public string $method,
        public array $arguments,
        public float $elapsed,
        public mixed $return,
    ) {

    }
}