<?php

namespace ManaPHP\Mvc;

/**
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class Application extends \ManaPHP\Http\Application
{
    public function getProviders()
    {
        return array_merge(parent::getProviders(), [Provider::class]);
    }

    public function authorize()
    {
        $this->authorization->authorize();
    }
}
