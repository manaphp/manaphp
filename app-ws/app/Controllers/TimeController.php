<?php

namespace App\Controllers;

use ManaPHP\Coroutine;

class TimeController extends Controller
{
    /**
     * @var array
     */
    protected $last_time = [];

    public function startAction()
    {
        Coroutine::create(
            function () {
                while (1) {
                    $time = time();
                    foreach ($this->last_time as $fd => $last_time) {
                        if ($time > $last_time) {
                            $this->wsServer->push($fd, date('Y-m-d H:i:s'));
                            $this->last_time[$fd] = $time;
                        }
                    }
                    @time_sleep_until($time + 1);
                }
            }
        );
    }

    public function openAction($fd)
    {
        $this->last_time[$fd] = 0;
    }

    public function closeAction($fd)
    {
        unset($this->last_time[$fd]);
    }
}