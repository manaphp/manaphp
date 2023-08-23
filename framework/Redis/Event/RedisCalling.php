<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Redis\Connection;

class RedisCalling
{
    public function __construct(
        public Connection $redis,
        public string $method,
        public array $arguments
    ) {

    }
}