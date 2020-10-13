<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Exception\MisuseException;
use Swoole\Coroutine\Channel as SwooleChannel;
use \SplQueue;

class Channel
{
    /**
     * @var int
     */
    protected $_capacity;

    /**
     * @var int
     */
    protected $_length;

    /**
     * @var \Swoole\Coroutine\Channel|\SplQueue
     */
    protected $_queue;

    /**
     * Channel constructor.
     *
     * @param int $capacity
     */
    public function __construct($capacity)
    {
        $this->_capacity = (int)$capacity;
        $this->_length = 0;
        $this->_queue = MANAPHP_COROUTINE_ENABLED ? new SwooleChannel($capacity) : new SplQueue();
    }

    /**
     * @param mixed $data
     *
     * @return void
     */
    public function push($data)
    {
        if ($this->_length + 1 > $this->_capacity) {
            throw new MisuseException('channel is full');
        }

        $this->_length++;
        $this->_queue->push($data);
    }

    /**
     * @param float $timeout
     *
     * @return mixed
     */
    public function pop($timeout = null)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $this->_length--;
            return $this->_queue->pop($timeout);
        } else {
            if ($this->_length === 0) {
                throw new MisuseException('channel is empty');
            }

            $this->_length--;
            return $this->_queue->pop();
        }
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->_length === 0;
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->_length === $this->_capacity;
    }

    /**
     * @return int
     */
    public function length()
    {
        return $this->_length;
    }

    public function capacity()
    {
        return $this->_capacity;
    }
}