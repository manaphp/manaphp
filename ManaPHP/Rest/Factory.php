<?php

namespace ManaPHP\Rest;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge($this->_definitions, [
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'identity' => 'ManaPHP\Identity\Adapter\Jwt'
        ]);
    }
}