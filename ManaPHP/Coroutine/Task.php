<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Component;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Task extends Component implements TaskInterface
{
    /**
     * @var callable
     */
    protected $_fn;

    /**
     * @var int
     */
    protected $_count;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $_channel;

    /**
     * Task constructor.
     *
     * @param callable $fn
     * @param int      $count
     */
    public function __construct($fn, $count = 1)
    {
        $this->_fn = $fn;
        $this->_count = $count;

        if (MANAPHP_COROUTINE_ENABLED) {
            $this->_channel = new Channel($count);

            for ($i = 0; $i < $count; $i++) {
                Coroutine::create([$this, 'routine']);
            }
        }
    }

    public function routine()
    {
        $fn = $this->_fn;
        while (($data = $this->_channel->pop()) !== false) {
            try {
                $fn($data);
            } catch (Throwable $throwable) {
                $this->logger->error($throwable);
            }
        }
    }

    /**
     * @param mixed $data
     * @param int   $timeout
     *
     * @return bool
     */
    public function push($data, $timeout = -1)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            return $this->_channel->push($data, $timeout);
        } else {
            $fn = $this->_fn;

            try {
                $fn($data);
            } catch (Throwable $throwable) {
                $this->logger->error($throwable);
            }
            return true;
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->_channel->close();
        }
    }
}