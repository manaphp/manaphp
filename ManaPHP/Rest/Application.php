<?php

namespace ManaPHP\Rest;

class Application extends \ManaPHP\Http\Application
{
    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
    }
}
