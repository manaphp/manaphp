<?php

namespace ManaPHP\Di;

interface ProviderInterface
{
    /**
     * @return array
     */
    public function getDefinitions();

    /**
     *
     * @param \ManaPHP\Di\ContainerInterface $container
     *
     * @return void
     */
    public function boot($container);
}