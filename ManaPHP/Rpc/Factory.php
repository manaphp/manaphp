<?php

namespace ManaPHP\Rpc;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->_definitions = array_merge(
            $this->_definitions, [
                'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
                'dispatcher'   => 'ManaPHP\Rpc\Dispatcher'
            ]
        );
    }
}