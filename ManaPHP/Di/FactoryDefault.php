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

        $this->_components = [
            'eventsManager' => 'ManaPHP\Event\Manager',
            'alias' => 'ManaPHP\Alias',
            'configure' => 'ManaPHP\Configure',
            'mvcHandler' => 'ManaPHP\Mvc\Handler',
            'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
            'router' => 'ManaPHP\Mvc\Router',
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'actionInvoker' => 'ManaPHP\Mvc\Action\Invoker',
            'url' => 'ManaPHP\Mvc\Url',
            'modelsManager' => 'ManaPHP\Db\Model\Manager',
            'modelsMetadata' => 'ManaPHP\Db\Model\Metadata\Adapter\Memory',
            'queryBuilder' => ['class' => 'ManaPHP\Mvc\Model\Query', 'shared' => false],
            'response' => 'ManaPHP\Http\Response',
            'cookies' => 'ManaPHP\Http\Cookies',
            'request' => 'ManaPHP\Http\Request',
            'filter' => 'ManaPHP\Http\Filter',
            'crypt' => 'ManaPHP\Security\Crypt',
            'flash' => 'ManaPHP\Mvc\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\Mvc\View\Flash\Adapter\Session',
            'session' => ['class' => 'ManaPHP\Http\Session', 'ManaPHP\Http\Session\Adapter\File'],
            'sessionBag' => ['class' => 'ManaPHP\Http\Session\Bag', 'shared' => false],
            'view' => 'ManaPHP\Mvc\View',
            'logger' => ['class' => 'ManaPHP\Logger', 'ManaPHP\Logger\Appender\File'],
            'renderer' => 'ManaPHP\Renderer',
            'debugger' => 'ManaPHP\Debugger',
            'password' => 'ManaPHP\Authentication\Password',
            'serializer' => 'ManaPHP\Serializer\Adapter\JsonPhp',
            'cache' => ['class' => 'ManaPHP\Cache', 'ManaPHP\Cache\Adapter\File'],
            'store' => ['class' => 'ManaPHP\Store', 'ManaPHP\Store\Adapter\File'],
            'counter' => ['class' => 'ManaPHP\Counter', 'ManaPHP\Counter\Adapter\Db'],
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
            'tasksMetadata' => ['class' => 'ManaPHP\Task\Metadata', 'ManaPHP\Task\Metadata\Adapter\Redis'],
            'viewsCache' => ['class' => 'ManaPHP\Cache\Adapter\File', 'dir' => '@data/viewsCache', 'extension' => '.html'],
            'modelsCache' => ['class' => 'ManaPHP\Cache\Adapter\File', 'dir' => '@data/modelsCache', 'extension' => '.json'],
            'htmlPurifier' => 'ManaPHP\Security\HtmlPurifier',
            'environment' => 'ManaPHP\Cli\Environment',
            'netConnectivity' => 'ManaPHP\Net\Connectivity',
            'db' => ['class' => 'ManaPHP\Db\Adapter\Mysql', 'mysql://root@localhost/test?charset=utf8'],
            'redis' => ['class' => 'ManaPHP\Redis', 'redis://localhost:6379/1/test?timeout=3&retry_interval=0&auth='],
            'mongodb' => ['class' => 'ManaPHP\Mongodb', 'mongodb://localhost/test']
        ];
    }
}