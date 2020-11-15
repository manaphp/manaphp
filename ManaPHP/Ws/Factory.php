<?php

namespace ManaPHP\Ws;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->_definitions = array_merge(
            $this->_definitions, [
                'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
                'wsServer'     => 'ManaPHP\Ws\Server\Adapter\Swoole',
                'dispatcher'   => 'ManaPHP\Ws\Dispatcher',
                'identity'     => 'ManaPHP\Identity\Adapter\Jwt',

                'wsPusherPlugin' => 'ManaPHP\Ws\PusherPlugin',
            ]
        );
    }
}