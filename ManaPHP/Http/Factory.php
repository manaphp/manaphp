<?php

namespace ManaPHP\Http;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge($this->_definitions, [
            'router' => 'ManaPHP\Router',
            'dispatcher' => 'ManaPHP\Dispatcher',
            'url' => 'ManaPHP\Url',
            'response' => 'ManaPHP\Http\Response',
            'request' => 'ManaPHP\Http\Request',
            'session' => 'ManaPHP\Http\Session\Adapter\File',
            'cookies' => 'ManaPHP\Http\Cookies',
            'captcha' => 'ManaPHP\Security\Captcha',
            'authorization' => 'ManaPHP\Authorization',
            'httpServer' => 'ManaPHP\Swoole\Http\Server',
            'globalsManager' => 'ManaPHP\Http\Globals\Manager',
        ]);
    }
}