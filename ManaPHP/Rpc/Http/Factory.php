<?php

namespace ManaPHP\Rpc\Http;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();
        $this->definitions = array_merge(
            $this->definitions, [
                'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
                'dispatcher'   => 'ManaPHP\Rpc\Dispatcher',

                'rpcCommand' => 'ManaPHP\Rpc\Http\Command',
            ]
        );

        if (PHP_SAPI === 'cli') {
            if (extension_loaded('swoole')) {
                $this->set('rpcServer', 'ManaPHP\Rpc\Http\Server\Adapter\Swoole');
            } else {
                $this->set('rpcServer', 'ManaPHP\Rpc\Http\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->set('rpcServer', 'ManaPHP\Rpc\Http\Server\Adapter\Php');
        } else {
            $this->set('rpcServer', 'ManaPHP\Rpc\Http\Server\Adapter\Fpm');
        }
    }
}