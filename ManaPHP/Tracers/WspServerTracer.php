<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Event\EventArgs;
use ManaPHP\Tracer;

class WspServerTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('wspServer:pushing', [$this, 'onPushing']);
    }

    public function onPushing(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'wspServer.pushing');
    }
}