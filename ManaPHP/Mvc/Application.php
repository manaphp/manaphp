<?php

namespace ManaPHP\Mvc;

class Application extends \ManaPHP\Http\Application
{
    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
    }
}