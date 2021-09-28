<?php

namespace ManaPHP\Di;

interface InvokerInterface
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     * @param callable                       $callable
     * @param array                          $parameters
     *
     * @return mixed
     */
    public function call($container, $callable, $parameters = []);
}