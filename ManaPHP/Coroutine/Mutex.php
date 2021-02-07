<?php

namespace ManaPHP\Coroutine;

class Mutex
{
    /**
     * @var \ManaPHP\Coroutine\Channel
     */
    protected $channel;

    public function __construct()
    {
        $this->channel = new Channel(1);
        $this->channel->push('');
    }

    /**
     * @return void
     */
    public function lock()
    {
        $this->channel->pop();
    }

    /**
     * @return void
     */
    public function unlock()
    {
        $this->channel->push('');
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->channel->isEmpty();
    }
}
