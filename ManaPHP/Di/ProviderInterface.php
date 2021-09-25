<?php

namespace ManaPHP\Di;

interface ProviderInterface
{
    /**
     * @return array
     */
    public function getDefinitions();

    /**
     * @return void
     */
    public function boot();
}