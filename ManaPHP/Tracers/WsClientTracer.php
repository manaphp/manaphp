<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WsClientTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('wsClient:send', [$this, 'onSend']);
        $this->attachEvent('wsClient:recv', [$this, 'onRecv']);
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onSend(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wsClient.send');
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onRecv(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wsClient.recv');
    }
}