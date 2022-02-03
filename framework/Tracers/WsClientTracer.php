<?php
declare(strict_types=1);
namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WsClientTracer extends Tracer
{
    public function listen():void
    {
        $this->attachEvent('wsClient:send', [$this, 'onSend']);
        $this->attachEvent('wsClient:recv', [$this, 'onRecv']);
    }

    public function onSend(EventArgs $eventArgs):void
    {
        $this->debug($eventArgs->data, 'wsClient.send');
    }

    public function onRecv(EventArgs $eventArgs):void
    {
        $this->debug($eventArgs->data, 'wsClient.recv');
    }
}