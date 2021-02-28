<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     * @param mixed                          $self
     *
     * @return void
     */
    public function setContainer($container, $self = null);
}