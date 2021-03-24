<?php

namespace ManaPHP\Http\Request;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Tracer extends \ManaPHP\Tracing\Tracer
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