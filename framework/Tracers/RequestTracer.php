<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Http\RequestInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Tracer;

class RequestTracer extends Tracer
{
    #[Inject]
    protected RequestInterface $request;

    public function listen(): void
    {
        $this->attachEvent('request:begin', [$this, 'onBegin']);
    }

    public function onBegin(): void
    {
        $this->debug($this->request->all(), 'http.request');
    }
}