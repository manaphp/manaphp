<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Tracer;
use ManaPHP\Ws\Pushing\Server\Event\ServerPushing;

class WspClientTracer extends Tracer
{
    public function onPush(#[Event] ServerPushing $event): void
    {
        $this->debug($event, 'wspClient.push');
    }
}