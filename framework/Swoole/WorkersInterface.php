<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use Swoole\Server;

interface WorkersInterface
{
    public function task(array|callable $task, array $arguments, int $task_worker_id): false|int;

    public function taskwait(array|callable $task, array $arguments, float $timeout, int $task_worker_id): mixed;

    public function sendMessage(array|callable $task, array $arguments, int $dst_worker_id): bool;

    public function getWorkerId(): int;

    public function getWorkerNum(): int;

    public function getTaskWorkerNum(): int;

    public function getServer(): Server;
}