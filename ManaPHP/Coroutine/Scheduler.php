<?php
namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;
use Throwable;
use Swoole\Coroutine\Channel;

class Scheduler extends Component implements SchedulerInterface
{
    /**
     * @var array
     */
    protected $_routines = [];

    /**
     * @var int
     */
    protected $_running_count;

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
     * @param \Swoole\Coroutine\Channel $sentinel
     * @param callable                  $routine
     */
    public function routine($sentinel, $routine)
    {
        list($fn, $args) = $routine;
        try {
            if (is_array($fn)) {
                list($object, $method) = $fn;
                $object->$method(...$args);
            } else {
                $fn(...$args);
            }
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }

        if (--$this->_running_count === 0) {
            $sentinel->push('end');
        }
    }

    /**
     * @return void
     */
    public function start()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->_running_count = count($this->_routines);
            $sentinel = new Channel(1);

            foreach ($this->_routines as $i => $routine) {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                Coroutine::create([$this, 'routine'], $sentinel, $routine);
            }

            $sentinel->pop();
        } else {
            $this->logger->debug('polling');
            foreach ($this->_routines as list($fn, $args)) {
                try {
                    if (is_array($fn)) {
                        /** @noinspection MultiAssignmentUsageInspection */
                        list($object, $method) = $fn;
                        $object->$method(...$args);
                    } else {
                        $fn(...$args);
                    }
                } catch (Throwable $throwable) {
                    $this->logger->error($throwable);
                }
            }
        }
    }

    /**
     * @param callable  $fn
     * @param array|int $args
     * @param int       $count
     *
     * @return void
     */
    public function parallel($fn, $args, $count = null)
    {
        if ($count === null && is_int($args)) {
            $count = $args;
            $args = [];
        }

        if ($count === null) {
            foreach ($args as $arg) {
                $this->add($fn, $arg);
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $this->add($fn, $args);
            }
        }

        $this->start();
    }
}