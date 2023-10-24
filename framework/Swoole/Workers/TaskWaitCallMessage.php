<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Workers;

class TaskWaitCallMessage
{
    public function __construct(
        public string $id,
        public string $method,
        public array $arguments
    ) {

    }
}