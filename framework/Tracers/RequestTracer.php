<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Tracer;

class RequestTracer extends Tracer
{
    #[Inject] protected RequestInterface $request;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        $this->debug($this->request->all(), 'http.request');
    }
}