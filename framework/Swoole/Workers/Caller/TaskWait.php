<?php
declare(strict_types=1);

namespace ManaPHP\Swoole\Workers\Caller;

use ManaPHP\Swoole\WorkersInterface;

class TaskWait
{
    public function __construct(
        public WorkersInterface $workers,
        public string $caller,
        public float $timeout,
        public int $task_worker_id
    ) {
    }

    public function __call($method, $args): mixed
    {
        return $this->workers->taskwait([$this->caller, $method], $args, $this->timeout, $this->task_worker_id);
    }
}