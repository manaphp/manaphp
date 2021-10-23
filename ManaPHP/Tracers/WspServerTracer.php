<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WspServerTracer extends Tracer
{
    public function listen()
    {
        $this->attachEvent('wspServer:pushing', [$this, 'onPushing']);
    }

    public function onPushing(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wspServer.pushing');
    }
}