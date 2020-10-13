<?php

namespace ManaPHP\Coroutine;

class Mutex
{
    /**
     * @var \ManaPHP\Coroutine\Channel
     */
    protected $_channel;

    public function __construct()
    {
        $this->_channel = new Channel(1);
        $this->_channel->push('');
    }

    public function lock()
    {
        $this->_channel->pop();
    }

    public function unlock()
    {
        $this->_channel->push('');
    }

    public function isLocked()
    {
        return $this->_channel->isEmpty();
    }
}
