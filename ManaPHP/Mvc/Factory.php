<?php
namespace ManaPHP\Mvc;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_components = array_merge($this->_components, [
            'router' => 'ManaPHP\Router',
            'dispatcher' => 'ManaPHP\Dispatcher',
            'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
            'url' => 'ManaPHP\Url',
            'response' => 'ManaPHP\Http\Response',
            'request' => 'ManaPHP\Http\Request',
            'view' => 'ManaPHP\View',
            'flash' => 'ManaPHP\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\View\Flash\Adapter\Session',
            'session' => 'ManaPHP\Http\Session\Adapter\File',
            'captcha' => 'ManaPHP\Security\Captcha',
            'viewsCache' => ['ManaPHP\Cache\Adapter\Redis', 'prefix' => 'cache:views:'],
            'cookies' => 'ManaPHP\Http\Cookies',
            'authorization' => 'ManaPHP\Authorization',
        ]);
    }
}