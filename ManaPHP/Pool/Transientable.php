<?php

namespace ManaPHP\Pool;

interface Transientable
{
    /**
     * @return static
     */
    public function getTransientWrapper();

    /**
     * @param mixed  $instance
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function transientCall($instance, $method, $arguments);
}