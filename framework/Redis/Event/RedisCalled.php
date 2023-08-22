<?php
declare(strict_types=1);

namespace ManaPHP\Redis\Event;

use ManaPHP\Redis\RedisInterface;

class RedisCalled
{
    public function __construct(
        public RedisInterface $redis,
        public string $method,
        public array $arguments,
        public mixed $return,
    ) {

    }
}