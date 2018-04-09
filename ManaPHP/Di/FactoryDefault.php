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
            'configure' => 'ManaPHP\Configuration\Configure',
            'settings' => 'ManaPHP\Configuration\Settings',
            'errorHandler' => 'ManaPHP\Mvc\ErrorHandler',
            'router' => 'ManaPHP\Router',
            'dispatcher' => 'ManaPHP\Mvc\Dispatcher',
            'actionInvoker' => 'ManaPHP\ActionInvoker',
            'url' => 'ManaPHP\View\Url',
            'modelsManager' => 'ManaPHP\Db\Model\Manager',
            'modelsMetadata' => 'ManaPHP\Db\Model\Metadata\Adapter\Memory',
            'modelsValidator' => 'ManaPHP\Model\Validator',
            'queryBuilder' => ['class' => 'ManaPHP\Db\Model\Query', 'shared' => false],
            'response' => 'ManaPHP\Http\Response',
            'cookies' => 'ManaPHP\Http\Cookies',
            'request' => 'ManaPHP\Http\Request',
            'filter' => 'ManaPHP\Http\Filter',
            'crypt' => 'ManaPHP\Security\Crypt',
            'flash' => 'ManaPHP\View\Flash\Adapter\Direct',
            'flashSession' => 'ManaPHP\View\Flash\Adapter\Session',
            'session' => 'ManaPHP\Http\Session',
            'view' => 'ManaPHP\View',
            'logger' => 'ManaPHP\Logger',
            'renderer' => 'ManaPHP\Renderer',
            'debugger' => 'ManaPHP\Debugger',
            'password' => 'ManaPHP\Authentication\Password',
            'serializer' => 'ManaPHP\Serializer\Adapter\JsonPhp',
            'cache' => 'ManaPHP\Cache',
            'store' => 'ManaPHP\Store',
            'counter' => 'ManaPHP\Counter',
            'httpClient' => 'ManaPHP\Curl\Easy',
            'captcha' => 'ManaPHP\Security\Captcha',
            'csrfToken' => 'ManaPHP\Security\CsrfToken',
            'authorization' => 'ManaPHP\Authorization\Bypass',
            'userIdentity' => 'ManaPHP\Authentication\UserIdentity',
            'paginator' => 'ManaPHP\Paginator',
            'filesystem' => 'ManaPHP\Filesystem\Adapter\File',
            'random' => 'ManaPHP\Security\Random',
            'messageQueue' => 'ManaPHP\Message\Queue',
            'crossword' => 'ManaPHP\Text\Crossword',
            'rateLimiter' => 'ManaPHP\Security\RateLimiter',
            'linearMeter' => 'ManaPHP\Meter\Linear',
            'roundMeter' => 'ManaPHP\Meter\Round',
            'secint' => 'ManaPHP\Security\Secint',
            'swordCompiler' => 'ManaPHP\Renderer\Engine\Sword\Compiler',
            'stopwatch' => 'ManaPHP\Stopwatch',
            'tasksMetadata' => ['class' => 'ManaPHP\Task\Metadata', 'ManaPHP\Task\Metadata\Adapter\Redis'],
            'viewsCache' => ['class' => 'ManaPHP\Cache\Engine\File', 'dir' => '@data/viewsCache', 'extension' => '.html'],
            'modelsCache' => ['class' => 'ManaPHP\Cache\Engine\File', 'dir' => '@data/modelsCache', 'extension' => '.json'],
            'htmlPurifier' => 'ManaPHP\Security\HtmlPurifier',
            'environment' => 'ManaPHP\Cli\Environment',
            'netConnectivity' => 'ManaPHP\Net\Connectivity',
            'db' => 'ManaPHP\Db\Adapter\Mysql',
            'redis' => 'ManaPHP\Redis',
            'mongodb' => 'ManaPHP\Mongodb',
            'translation' => 'ManaPHP\I18n\Translation',
            'rabbitmq' => 'ManaPHP\Amqp',
            'relationsManager' => 'ManaPHP\Model\Relation\Manager'
        ];
    }
}