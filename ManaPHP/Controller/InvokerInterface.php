<?php

namespace ManaPHP\Controller;

interface InvokerInterface
{
    /**
     * @param object $instance
     * @param string $method
     *
     * @return mixed
     */
    public function invoke($instance, $method);
}