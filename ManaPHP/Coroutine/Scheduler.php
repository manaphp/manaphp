<?php
namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use ManaPHP\ContextManager;
use Swoole\Atomic;
use Swoole\Coroutine;
use Throwable;

class Scheduler extends Component implements SchedulerInterface
{
    /**
     * @var \Swoole\Atomic
     */
    protected $_atomic;
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

    public function routine($routine)
    {
        list($fn, $args) = $routine;
        try {
            $throwable = null;
            call_user_func_array($fn, $args);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable);
        }
        if (--$this->_running_count === 0) {
            $this->_atomic->add(1);
        }

        ContextManager::reset();
    }

    /**
     * @return void
     */
    public function start()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->_running_count = count($this->_routines);
            $this->_atomic = new Atomic(0);

            foreach ($this->_routines as $i => $routine) {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                Coroutine::create([$this, 'routine'], $routine);
            }

            $this->_atomic->wait();
            $this->_atomic = null;
        } else {
            $this->logger->debug('polling');
            foreach ($this->_routines as $routine) {
                list($fn, $args) = $routine;
                try {
                    call_user_func_array($fn, $args);
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