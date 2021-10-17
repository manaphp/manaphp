<?php

namespace ManaPHP\Socket;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'request'      => 'ManaPHP\Socket\Request',
            'response'     => 'ManaPHP\Socket\Response',
            'socketServer' => 'ManaPHP\Socket\Server'
        ];
}