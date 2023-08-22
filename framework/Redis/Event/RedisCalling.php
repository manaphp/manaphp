<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Redis\RedisInterface;

class RedisCalling
{
    public function __construct(
        public RedisInterface $redis,
        public string $method,
        public array $arguments
    ) {

    }
}