<?php
namespace ManaPHP\Rpc;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->_definitions = array_merge($this->_definitions, [
            'rpcServer' => 'ManaPHP\Rpc\Server\Adapter\Swoole',
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'contextManager' => 'ManaPHP\Coroutine\Context\Manager',
            'dispatcher' => 'ManaPHP\Rpc\Dispatcher']);
    }
}