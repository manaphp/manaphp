<?php

namespace ManaPHP\Di;

interface FactoryInterface
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     * @param string                         $name
     * @param array                          $parameters
     *
     * @return mixed
     */
    public function make($container, $name, $parameters = []);
}