<?php
declare(strict_types=1);

namespace ManaPHP\Tracers;

use ManaPHP\Tracer;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class RequestTracer extends Tracer
{
    public function listen(): void
    {
        $this->attachEvent('request:begin', [$this, 'onBegin']);
    }

    public function onBegin(): void
    {
        $this->debug($this->request->all(), 'http.request');
    }
}