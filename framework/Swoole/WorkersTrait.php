<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Swoole\Workers\Caller\SendMessage;
use ManaPHP\Swoole\Workers\Caller\Task;
use ManaPHP\Swoole\Workers\Caller\TaskWait;

trait WorkersTrait
{
    #[Autowired] protected WorkersInterface $workers;

    public function task(int $task_worker_id): static|Task
    {
        return new Task($this->workers, static::class, $task_worker_id);
    }

    public function sendMessage(int $task_worker_id): static|SendMessage
    {
        return new SendMessage($this->workers, static::class, $task_worker_id);
    }

    public function taskwait(float $timeout, int $task_worker_id): static|TaskWait
    {
        return new TaskWait($this->workers, static::class, $timeout, $task_worker_id);
    }
}