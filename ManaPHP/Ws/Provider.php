<?php

namespace ManaPHP\Ws;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'wsHandler'    => 'ManaPHP\Ws\Handler',
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'wsServer'     => 'ManaPHP\Ws\Server\Adapter\Swoole',
            'dispatcher'   => 'ManaPHP\Ws\Dispatcher',
            'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
            'wspServer'    => 'ManaPHP\Ws\Pushing\Server',
            'chatServer'   => 'ManaPHP\Ws\Chatting\Server',

            'wspServerTracer' => 'ManaPHP\Ws\Pushing\Server\Tracer',
            'wspClientTracer' => 'ManaPHP\Ws\Pushing\Client\Tracer',
        ];
}