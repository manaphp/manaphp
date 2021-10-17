<?php

namespace ManaPHP\Rpc\Http;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'rpcHandler'   => 'ManaPHP\Rpc\Http\Handler',
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'dispatcher'   => 'ManaPHP\Rpc\Dispatcher',

            'rpcCommand' => 'ManaPHP\Commands\RpcCommand',
            'rpcServer'  => 'ManaPHP\Rpc\Server',
        ];
}