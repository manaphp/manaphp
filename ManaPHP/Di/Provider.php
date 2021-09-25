<?php

namespace ManaPHP\Di;

class Provider implements ProviderInterface
{
    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    public function boot()
    {

    }
}