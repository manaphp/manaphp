<?php

namespace ManaPHP\Di;

use ManaPHP\Di;

/**
 * Class ManaPHP\Di\FactoryDefault
 *
 * @package di
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
            'configure' => 'ManaPHP\Configure',
            'mvcHandler' => 'ManaPHP\Mvc\Handler',
            'router' => 'ManaPHP\Mvc\Router',
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'actionInvoker' => 'ManaPHP\Mvc\Action\Invoker',
            'url' => 'ManaPHP\Mvc\Url',
            'modelsManager' => 'ManaPHP\Db\Model\Manager',
            'modelsMetadata' => 'ManaPHP\Db\Model\Metadata\Adapter\Memory',
            'queryBuilder' => ['ManaPHP\Mvc\Model\Query', false],
            'response' => 'ManaPHP\Http\Response',
            'cookies' => 'ManaPHP\Http\Cookies',
            'request' => 'ManaPHP\Http\Request',
            'filter' => 'ManaPHP\Http\Filter',
            'crypt' => 'ManaPHP\Security\Crypt',
            'flash' => 'ManaPHP\Mvc\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\Mvc\View\Flash\Adapter\Session',
            'session' => ['ManaPHP\Http\Session', ['ManaPHP\Http\Session\Adapter\File']],
            'sessionBag' => ['ManaPHP\Http\Session\Bag', false],
            'view' => 'ManaPHP\Mvc\View',
            'logger' => ['ManaPHP\Logger', ['ManaPHP\Logger\Adapter\File']],
            'renderer' => 'ManaPHP\Renderer',
            'debugger' => 'ManaPHP\Debugger',
            'password' => 'ManaPHP\Authentication\Password',
            'serializer' => 'ManaPHP\Serializer\Adapter\JsonPhp',
            'cache' => ['ManaPHP\Cache', ['ManaPHP\Cache\Adapter\File']],
            'store' => ['ManaPHP\Store', ['ManaPHP\Store\Adapter\File']],
            'counter' => ['ManaPHP\Counter', ['ManaPHP\Counter\Adapter\Db']],
            'httpClient' => 'ManaPHP\Http\Client',
            'captcha' => 'ManaPHP\Security\Captcha',
            'csrfToken' => 'ManaPHP\Security\CsrfToken',
            'authorization' => 'ManaPHP\Authorization\Bypass',
            'userIdentity' => 'ManaPHP\Authentication\UserIdentity',
            'paginator' => 'ManaPHP\Paginator',
            'filesystem' => 'ManaPHP\Filesystem\Adapter\File',
            'random' => 'ManaPHP\Security\Random',
            'messageQueue' => 'ManaPHP\Message\Queue\Adapter\Db',
            'crossword' => 'ManaPHP\Text\Crossword',
            'rateLimiter' => 'ManaPHP\Security\RateLimiter\Adapter\Db',
            'linearMeter' => 'ManaPHP\Meter\Linear',
            'roundMeter' => 'ManaPHP\Meter\Round',
            'secint' => 'ManaPHP\Security\Secint',
            'swordCompiler' => 'ManaPHP\Renderer\Engine\Sword\Compiler',
            'stopwatch' => 'ManaPHP\Stopwatch',
            'tasksMetadata' => ['ManaPHP\Task\Metadata', ['ManaPHP\Task\Metadata\Adapter\Redis']],
            'viewsCache' => ['ManaPHP\Cache\Adapter\File', [['dir' => '@data/viewsCache', 'extension' => '.html']]],
            'modelsCache' => ['ManaPHP\Cache\Adapter\File', [['dir' => '@data/modelsCache', 'extension' => '.json']]],
            'htmlPurifier' => 'ManaPHP\Security\HtmlPurifier',
            'environment' => 'ManaPHP\Cli\Environment',
            'netConnectivity' => 'ManaPHP\Net\Connectivity',
            'dbQuery' => ['ManaPHP\Db\Query', false],
        ];
    }
}