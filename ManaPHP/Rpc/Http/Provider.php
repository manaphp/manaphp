<?php

namespace ManaPHP\Rpc\Http;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'dispatcher'   => 'ManaPHP\Rpc\Dispatcher',

            'rpcCommand' => 'ManaPHP\Rpc\Http\Command'
        ];

    public function __construct()
    {
        $this->definitions['rpcServer'] = (function () {
            if (PHP_SAPI === 'cli') {
                if (extension_loaded('swoole')) {
                    return 'ManaPHP\Rpc\Http\Server\Adapter\Swoole';
                } else {
                    return 'ManaPHP\Rpc\Http\Server\Adapter\Php';
                }
            } elseif (PHP_SAPI === 'cli-server') {
                return 'ManaPHP\Rpc\Http\Server\Adapter\Php';
            } else {
                return 'ManaPHP\Rpc\Http\Server\Adapter\Fpm';
            }
        })();
    }
}