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

        $this->_definitions = [
            'eventsManager' => 'ManaPHP\Event\Manager',
            'poolManager' => 'ManaPHP\Pool\Manager',
            'alias' => 'ManaPHP\Alias',
            'aop' => 'ManaPHP\Aop',
            'dotenv' => 'ManaPHP\Dotenv',
            'configure' => 'ManaPHP\Configuration\Configure',
            'settings' => 'ManaPHP\Configuration\Settings\Adapter\Redis',
            'invoker' => 'ManaPHP\Invoker',
            'modelsMetadata' => 'ManaPHP\Db\Model\Metadata\Adapter\Memory',
            'validator' => 'ManaPHP\Validator',
            'crypt' => 'ManaPHP\Security\Crypt',
            'logger' => 'ManaPHP\Logger\Adapter\File',
            'renderer' => 'ManaPHP\Renderer',
            'assetBundle' => 'ManaPHP\Renderer\AssetBundle',
            'cache' => 'ManaPHP\Cache\Adapter\Redis',
            'ipcCache' => 'ManaPHP\Ipc\Cache\Adapter\Apcu',
            'httpClient' => 'ManaPHP\Http\Client\Adapter\Stream',
            'restClient' => 'ManaPHP\Http\Client\Adapter\Stream',
            'paginator' => 'ManaPHP\Paginator',
            'random' => 'ManaPHP\Security\Random',
            'messageQueue' => 'ManaPHP\Message\Queue\Adapter\Redis',
            'swordCompiler' => 'ManaPHP\Renderer\Engine\Sword\Compiler',
            'htmlPurifier' => 'ManaPHP\Security\HtmlPurifier',
            'db' => 'ManaPHP\Db',
            'redis' => 'ManaPHP\Redis',
            'redisCache' => '@redis',
            'redisDb' => '@redis',
            'redisBroker' => '@redis',
            'mongodb' => 'ManaPHP\Mongodb',
            'translator' => 'ManaPHP\I18n\Translator',
            'rabbitmq' => 'ManaPHP\Amqp',
            'relationsManager' => 'ManaPHP\Model\Relation\Manager',
            'mailer' => 'ManaPHP\Mailer\Adapter\Smtp',
            'aclBuilder' => 'ManaPHP\Authorization\AclBuilder',
            'bosClient' => 'ManaPHP\Bos\Client',
            'wsPusher' => 'ManaPHP\WebSocket\Pusher',
            'identity' => 'ManaPHP\Identity',
            'coroutineManager' => 'ManaPHP\Coroutine\Manager',
            'jwt' => 'ManaPHP\Identity\Adapter\Jwt',
            'wsClient' => 'ManaPHP\WebSocket\Client',
            'pubSub' => 'ManaPHP\Message\PubSub\Adapter\Redis',
            'coroutineSerial' => 'ManaPHP\Coroutine\Serial',
        ];
    }
}
