<?php

namespace App\Controllers;

use ManaPHP\WebSocket\Controller;
use Swoole\Coroutine;

class TimeController extends Controller
{
    /**
     * @var array
     */
    protected $_last_time = [];

    public function startAction()
    {
        Coroutine::create(
            function () {
                while (1) {
                    $time = time();
                    foreach ($this->_last_time as $fd => $last_time) {
                        if ($time > $last_time) {
                            $this->wsServer->push($fd, date('Y-m-d H:i:s'));
                            $this->_last_time[$fd] = $time;
                        }
                    }
                    time_sleep_until($time + 1);
                }
            }
        );
    }

    public function openAction($fd)
    {
        $this->_last_time[$fd] = 0;
    }

    public function closeAction($fd)
    {
        unset($this->_last_time[$fd]);
    }
}