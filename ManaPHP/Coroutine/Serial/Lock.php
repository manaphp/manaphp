<?php

namespace ManaPHP\Coroutine\Serial;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class Lock
{
    /**
     * @var int
     */
    protected $_cid;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $_channel;

    /**
     * @var int
     */
    protected $_count = 0;

    public function __construct()
    {
        $channel = new Channel(1);
        $channel->push(1);

        $this->_channel = $channel;
    }

    public function lock()
    {
        $cid = Coroutine::getcid();

        if ($cid !== $this->_cid) {
            $this->_channel->pop();
            $this->_cid = $cid;
        }

        $this->_count++;
    }

    public function unlock()
    {
        if ($this->_count > 0) {
            $this->_count--;

            if ($this->_count === 0) {
                $this->_cid = null;
                $this->_channel->push(1);
            }
        }
    }
}