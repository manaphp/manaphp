<?php

namespace ManaPHP\Di;

interface InjectorInterface
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function make($name, $parameters = []);

    /**
     * @param callable $callable
     * @param array    $parameters
     *
     * @return mixed
     */
    public function call($callable, $parameters = []);
}