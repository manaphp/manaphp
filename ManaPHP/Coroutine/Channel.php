<?php

namespace ManaPHP\Coroutine;

use ManaPHP\Exception\MisuseException;
use SplQueue;
use Swoole\Coroutine\Channel as SwooleChannel;

class Channel
{
    /**
     * @var int
     */
    protected $capacity;

    /**
     * @var int
     */
    protected $length;

    /**
     * @var \Swoole\Coroutine\Channel|\SplQueue
     */
    protected $queue;

    /**
     * @param int $capacity
     */
    public function __construct($capacity)
    {
        $this->capacity = (int)$capacity;
        $this->length = 0;
        $this->queue = MANAPHP_COROUTINE_ENABLED ? new SwooleChannel($capacity) : new SplQueue();
    }

    /**
     * @param mixed $data
     *
     * @return void
     */
    public function push($data)
    {
        if ($this->length + 1 > $this->capacity) {
            throw new MisuseException('channel is full');
        }

        $this->length++;
        $this->queue->push($data);
    }

    /**
     * @param float $timeout
     *
     * @return mixed
     */
    public function pop($timeout = null)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $data = $this->queue->pop($timeout);
        } else {
            if ($this->length === 0) {
                throw new MisuseException('channel is empty');
            }

            $data = $this->queue->pop();
        }

        $this->length--;

        return $data;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->length === 0;
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->length === $this->capacity;
    }

    /**
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function capacity()
    {
        return $this->capacity;
    }
}