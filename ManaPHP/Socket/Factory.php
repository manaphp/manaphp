<?php

namespace ManaPHP\Socket;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();
        $this->_definitions = array_merge(
            $this->_definitions, [
                'request'      => 'ManaPHP\Socket\Request',
                'response'     => 'ManaPHP\Socket\Response',
                'socketServer' => 'ManaPHP\Socket\Server\Adapter\Swoole'
            ]
        );
    }
}