<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Workers\Caller;

use ManaPHP\Swoole\WorkersInterface;

class Task
{
    public function __construct(
        public WorkersInterface $workers,
        public string $caller,
        public int $task_worker_id,
        public ?float $timeout,
    ) {
    }

    public function __call($method, $args): mixed
    {
        return $this->workers->task([$this->caller, $method], $args, $this->task_worker_id, $this->timeout);
    }
}