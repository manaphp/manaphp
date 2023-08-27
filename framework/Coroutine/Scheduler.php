<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Di\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Scheduler implements SchedulerInterface
{
    #[Inject] protected LoggerInterface $logger;

    protected array $tasks = [];

    public function add(callable $fn, ...$args): static
    {
        $this->tasks[] = [$fn, $args];

        return $this;
    }

    public function routine(int $id, Channel $channel, array $task): void
    {
        list($fn, $args) = $task;
        try {
            $return = $fn(...$args);
        } catch (Throwable $throwable) {
            $return = null;
            $this->logger->error('', ['exception' => $throwable]);
        }
        $channel->push([$id, $return]);
    }

    public function start(): array
    {
        $returns = [];

        if (MANAPHP_COROUTINE_ENABLED) {
            $tasks_count = count($this->tasks);

            $channel = new Channel($tasks_count);

            foreach ($this->tasks as $id => $task) {
                $returns[$id] = null;
                Coroutine::create([$this, 'routine'], $id, $channel, $task);
            }

            for ($i = 0; $i < $tasks_count; $i++) {
                list($id, $return) = $channel->pop();
                $returns[$id] = $return;
            }
        } else {
            foreach ($this->tasks as $id => list($fn, $args)) {
                try {
                    $return = $fn(...$args);
                } catch (Throwable $throwable) {
                    $return = null;
                    $this->logger->error('', ['exception' => $throwable]);
                }

                $returns[$id] = $return;
            }
        }

        return $returns;
    }
}
