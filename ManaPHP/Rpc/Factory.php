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

        if (PHP_SAPI === 'cli') {
            if (extension_loaded('swoole')) {
                $this->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Swoole');
            } else {
                $this->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Php');
        } else {
            $this->setShared('rpcServer', 'ManaPHP\Rpc\Server\Adapter\Fpm');
        }
    }
}