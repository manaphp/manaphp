<?php

namespace ManaPHP\Di;

interface Injectable
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return void
     */
    public function setContainer($container);
}