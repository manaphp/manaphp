<?php

namespace ManaPHP\Aop;

interface Proxyable
{
    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __proxyCall($method, $arguments);
}