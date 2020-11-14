<?php

namespace ManaPHP\Http;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge(
            $this->_definitions, [
                'router'         => 'ManaPHP\Router',
                'dispatcher'     => 'ManaPHP\Http\Dispatcher',
                'url'            => 'ManaPHP\Http\Url',
                'response'       => 'ManaPHP\Http\Response',
                'request'        => 'ManaPHP\Http\Request',
                'session'        => 'ManaPHP\Http\Session\Adapter\File',
                'cookies'        => 'ManaPHP\Http\Cookies',
                'captcha'        => 'ManaPHP\Http\Captcha',
                'authorization'  => 'ManaPHP\Authorization',
                'globalsManager' => 'ManaPHP\Http\Globals\Manager',
            ]
        );

        if (PHP_SAPI === 'cli') {
            if (class_exists('Workerman\Worker')) {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Workerman');
            } elseif (extension_loaded('swoole')) {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Swoole');
            } else {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
        } else {
            $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Fpm');
        }
    }
}