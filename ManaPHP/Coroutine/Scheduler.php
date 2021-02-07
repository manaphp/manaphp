<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Scheduler extends Component implements SchedulerInterface
{
    /**
     * @var array
     */
    protected $tasks = [];

    /**
     * @param callable $fn
     * @param mixed    ...$args
     *
     * @return static
     */
    public function add($fn, ...$args)
    {
        $this->tasks[] = [$fn, $args];

        return $this;
    }

    /**
     * @param int                       $id
     * @param \Swoole\Coroutine\Channel $channel
     * @param array                     $task
     *
     * @return void
     */
    public function routine($id, $channel, $task)
    {
        list($fn, $args) = $task;
        try {
            $return = $fn(...$args);
        } catch (Throwable $throwable) {
            $return = null;
            $this->logger->error($throwable);
        }
        $channel->push([$id, $return]);
    }

    /**
     * @return array
     */
    public function start()
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
                    $this->logger->error($throwable);
                }

                $returns[$id] = $return;
            }
        }

        return $returns;
    }
}
