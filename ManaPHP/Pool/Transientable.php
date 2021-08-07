<?php

namespace ManaPHP\Pool;

interface Transientable
{
    /**
     * @param string $type
     *
     * @return static
     */
    public function getTransientWrapper($type = 'default');

    /**
     * @param mixed  $instance
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function transientCall($instance, $method, $arguments);
}