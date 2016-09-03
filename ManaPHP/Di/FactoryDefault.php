<?php

namespace ManaPHP\Di;

use ManaPHP\Di;

/**
 * ManaPHP\Di\FactoryDefault
 *
 * This is a variant of the standard ManaPHP\Di. By default it automatically
 * registers all the services provided by the framework. Thanks to this, the developer does not need
 * to register each service individually providing a full stack framework
 */
class FactoryDefault extends Di
{
    /**
     * \ManaPHP\Di\FactoryDefault constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_services = [
            'eventsManager' => 'ManaPHP\Event\Manager',
            'alias' => 'ManaPHP\Alias',
            'router' => 'ManaPHP\Mvc\Router',
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'url' => 'ManaPHP\Mvc\Url',
            'modelsManager' => 'ManaPHP\Mvc\Model\Manager',
            'modelsMetadata' => 'ManaPHP\Mvc\Model\Metadata\Adapter\Memory',
            'response' => 'ManaPHP\Http\Response',
            'cookies' => 'ManaPHP\Http\Cookies',
            'request' => 'ManaPHP\Http\Request',
            'filter' => 'ManaPHP\Http\Filter',
            'escaper' => 'ManaPHP\Escaper',
            'crypt' => 'ManaPHP\Security\Crypt',
            'flash' => 'ManaPHP\Mvc\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\Flash\Adapter\Session',
            'tag' => 'ManaPHP\Mvc\View\Tag',
            'session' => ['class' => 'ManaPHP\Http\Session', 'parameters' => ['ManaPHP\Http\Session\Adapter\File']],
            'sessionBag' => ['ManaPHP\Http\Session\Bag', false],
            'loader' => 'ManaPHP\Loader',
            'view' => 'ManaPHP\Mvc\View',
            'logger' => ['class' => 'ManaPHP\Logger', 'parameters' => ['ManaPHP\Logger\Adapter\File']],
            'renderer' => 'ManaPHP\Renderer',
            'debugger' => 'ManaPHP\Debugger',
            'password' => 'ManaPHP\Authentication\Password',
            'serializer' => 'ManaPHP\Serializer\Adapter\JsonPhp',
            'cache' => ['class' => 'ManaPHP\Cache', 'parameters' => ['ManaPHP\Cache\Adapter\File']],
            'store' => ['class' => 'ManaPHP\Store', 'parameters' => ['ManaPHP\Store\Adapter\File']],
            'counter' => ['class' => 'ManaPHP\Counter', 'parameters' => ['ManaPHP\Counter\Adapter\Db']],
            'httpClient' => 'ManaPHP\Http\Client',
            'captcha' => 'ManaPHP\Security\Captcha',
            'csrfToken' => 'ManaPHP\Security\CsrfToken',
            'authorization' => 'ManaPHP\Authorization\Bypass',
            'userIdentity' => 'ManaPHP\Authentication\UserIdentity',
            'paginator' => 'ManaPHP\Paginator',
            'tasksMetadata' => ['class' => 'ManaPHP\Task\Metadata', 'parameters' => ['ManaPHP\Task\Metadata\Adapter\Redis']],
            'viewsCache' => [
                'class' => 'ManaPHP\Cache\Adapter\File',
                'parameters' => [['dir' => '@data/viewsCache', 'extension' => '.html']]
            ],
            'modelsCache' => [
                'class' => 'ManaPHP\Cache\Adapter\File',
                'parameters' => [['dir' => '@data/modelsCache', 'extension' => '.json']]
            ],
        ];
    }
}