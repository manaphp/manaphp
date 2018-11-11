<?php
namespace ManaPHP\Rest;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_components = array_merge($this->_components, [
            'router' => 'ManaPHP\Router',
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
            'url' => 'ManaPHP\Url',
            'response' => 'ManaPHP\Http\Response',
            'request' => 'ManaPHP\Http\Request',
            'session' => 'ManaPHP\Http\Session',
            'cookies' => 'ManaPHP\Http\Cookies',
            'captcha' => 'ManaPHP\Security\Captcha',
            'csrfToken' => 'ManaPHP\Security\CsrfToken',
            'debugger' => 'ManaPHP\Debugger',
            'authorization' => 'ManaPHP\Authorization',
        ]);
    }
}