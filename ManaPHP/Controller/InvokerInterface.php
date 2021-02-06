<?php

namespace ManaPHP\Controller;

interface InvokerInterface
{
    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $method
     *
     * @return mixed
     */
    public function invoke($controller, $method);
}