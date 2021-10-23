<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WspClientTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('wspClient:push', [$this, 'onPush']);
    }

    public function onPush(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wspClient.push');
    }
}