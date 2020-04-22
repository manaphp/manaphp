<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Scheduler extends Component implements SchedulerInterface
{
    /**
     * @var array
     */
    protected $_routines = [];

    /**
     * @param callable $fn
     * @param mixed    ...$args
     *
     * @return static
     */
    public function add($fn, ...$args)
    {
        $this->_routines[] = [$fn, $args];

        return $this;
    }

    /**
     * @param int                       $id
     * @param \Swoole\Coroutine\Channel $channel
     * @param callable                  $routine
     */
    public function routine($id, $channel, $routine)
    {
        list($fn, $args) = $routine;
        try {
            if (is_array($fn)) {
                list($object, $method) = $fn;
                $return = $object->$method(...$args);
            } else {
                $return = $fn(...$args);
            }
        } catch (Throwable $throwable) {
            $return = null;
            $this->logger->error($throwable);
        }
        $channel->push([$id, $return]);
    }

    /**
     *
     * @return array
     */
    public function start()
    {
        $returns = [];

        if (MANAPHP_COROUTINE_ENABLED) {
            $routines_count = count($this->_routines);

            $channel = new Channel($routines_count);

            foreach ($this->_routines as $id => $routine) {
                $returns[$id] = null;
                Coroutine::create([$this, 'routine'], $id, $channel, $routine);
            }

            for ($i = 0; $i < $routines_count; $i++) {
                list($id, $return) = $channel->pop();
                $returns[$id] = $return;
            }
        } else {
            foreach ($this->_routines as $id => list($fn, $args)) {
                try {
                    if (is_array($fn)) {
                        /** @noinspection MultiAssignmentUsageInspection */
                        list($object, $method) = $fn;
                        $return = $object->$method(...$args);
                    } else {
                        $return = $fn(...$args);
                    }
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
