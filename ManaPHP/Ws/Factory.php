<?php

namespace ManaPHP\Ws;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->definitions = array_merge(
            $this->definitions, [
                'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
                'wsServer'     => 'ManaPHP\Ws\Server\Adapter\Swoole',
                'dispatcher'   => 'ManaPHP\Ws\Dispatcher',
                'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
                'wspServer'    => 'ManaPHP\Ws\Pushing\Server',
                'chatServer'   => 'ManaPHP\Ws\Chatting\Server',

                'wspServerTracer' => 'ManaPHP\Ws\Pushing\Server\Tracer',
                'wspClientTracer' => 'ManaPHP\Ws\Pushing\Client\Tracer',
            ]
        );
    }
}