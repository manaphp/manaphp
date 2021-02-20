<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return static
     */
    public function setContainer($container);
}