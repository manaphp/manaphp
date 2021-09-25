<?php

namespace ManaPHP\Rest;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Jwt'
        ];
}