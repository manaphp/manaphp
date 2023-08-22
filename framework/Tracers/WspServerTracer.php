<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Tracer;
use ManaPHP\Ws\Pushing\Server\Event\ServerPushing;

class WspServerTracer extends Tracer
{
    public function onPushing(#[Event] ServerPushing $event): void
    {
        $this->debug($event, 'wspServer.pushing');
    }
}