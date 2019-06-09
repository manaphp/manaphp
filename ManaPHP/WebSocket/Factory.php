<?php
namespace ManaPHP\WebSocket;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->_definitions = array_merge($this->_definitions, [
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'wsServer' => 'ManaPHP\WebSocket\Server\Adapter\Swoole',
            'identity' => 'ManaPHP\Identity\Adapter\Jwt',
            'dispatcher' => 'ManaPHP\WebSocket\Dispatcher']);
    }
}