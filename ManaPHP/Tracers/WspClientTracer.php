<?php

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WspClientTracer extends Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('wspClient:push', [$this, 'onPush']);
    }

    public function onPush(EventArgs $eventArgs)
    {
        $this->debug($eventArgs->data, 'wspClient.push');
    }
}