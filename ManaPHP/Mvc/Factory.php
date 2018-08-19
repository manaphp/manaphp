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
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'actionInvoker' => 'ManaPHP\ActionInvoker',
            'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
            'url' => 'ManaPHP\View\Url',
            'response' => 'ManaPHP\Http\Response',
            'request' => 'ManaPHP\Http\Request',
            'html' => 'ManaPHP\View\Html',
            'view' => 'ManaPHP\View',
            'flash' => 'ManaPHP\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\View\Flash\Adapter\Session',
            'session' => 'ManaPHP\Http\Session',
            'captcha' => 'ManaPHP\Security\Captcha',
            'csrfToken' => 'ManaPHP\Security\CsrfToken',
            'viewsCache' => ['class' => 'ManaPHP\Cache\Engine\File', 'dir' => '@data/viewsCache', 'extension' => '.html'],
            'cookies' => 'ManaPHP\Http\Cookies',
            'debugger' => 'ManaPHP\Debugger',
            'authorization' => 'ManaPHP\Authorization\Bypass',
        ]);
    }
}