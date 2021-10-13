<?php

namespace ManaPHP\Tracers;

use ManaPHP\Tracer;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class RequestTracer extends Tracer
{
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->attachEvent('request:begin', [$this, 'onBegin']);
    }

    /**
     * @return void
     */
    public function onBegin()
    {
        $this->debug($this->request->get(), 'http.request');
    }
}