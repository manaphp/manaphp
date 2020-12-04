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

    /**
     * @return void
     */
    public function lock()
    {
        $this->_channel->pop();
    }

    /**
     * @return void
     */
    public function unlock()
    {
        $this->_channel->push('');
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->_channel->isEmpty();
    }
}
