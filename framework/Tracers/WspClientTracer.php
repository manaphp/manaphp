<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\EventArgs;
use ManaPHP\Tracer;

class WspClientTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('wspClient:push', [$this, 'onPush']);
    }

    public function onPush(EventArgs $eventArgs): void
    {
        $this->debug($eventArgs->data, 'wspClient.push');
    }
}