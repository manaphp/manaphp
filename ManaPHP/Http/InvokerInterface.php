<?php

namespace ManaPHP\Http;

interface InvokerInterface
{
    /**
     * @param \ManaPHP\Http\Controller $controller
     * @param string                   $method
     *
     * @return mixed
     */
    public function invoke($controller, $method);
}