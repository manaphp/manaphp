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
            'eventsManager'    => 'ManaPHP\Event\Manager',
            'poolManager'      => 'ManaPHP\Pool\Manager',
            'alias'            => 'ManaPHP\Alias',
            'aopCutter'        => 'ManaPHP\Aop\Cutter',
            'dotenv'           => 'ManaPHP\Configuration\Dotenv',
            'configure'        => 'ManaPHP\Configuration\Configure',
            'settings'         => 'ManaPHP\Configuration\Settings\Adapter\Redis',
            'invoker'          => 'ManaPHP\Invoker',
            'modelsMetadata'   => 'ManaPHP\Data\Db\Model\Metadata\Adapter\Memory',
            'validator'        => 'ManaPHP\Validating\Validator',
            'crypt'            => 'ManaPHP\Security\Crypt',
            'logger'           => 'ManaPHP\Logging\Logger\Adapter\File',
            'renderer'         => 'ManaPHP\Html\Renderer',
            'assetBundle'      => 'ManaPHP\Html\Renderer\AssetBundle',
            'cache'            => 'ManaPHP\Caching\Cache\Adapter\Redis',
            'httpClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Curl'],
            'restClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Stream'],
            'paginator'        => 'ManaPHP\Data\Paginator',
            'random'           => 'ManaPHP\Security\Random',
            'msgQueue'         => 'ManaPHP\Messaging\Queue\Adapter\Redis',
            'swordCompiler'    => 'ManaPHP\Html\Renderer\Engine\Sword\Compiler',
            'htmlPurifier'     => 'ManaPHP\Html\Purifier',
            'db'               => 'ManaPHP\Data\Db',
            'redis'            => 'ManaPHP\Data\Redis',
            'redisCache'       => '@redis',
            'redisDb'          => '@redis',
            'redisBroker'      => '@redis',
            'mongodb'          => 'ManaPHP\Data\Mongodb',
            'translator'       => 'ManaPHP\I18n\Translator',
            'rabbitmq'         => 'ManaPHP\Messaging\Amqp',
            'relationsManager' => 'ManaPHP\Data\Relation\Manager',
            'mailer'           => 'ManaPHP\Mailing\Mailer\Adapter\Smtp',
            'aclBuilder'       => 'ManaPHP\Http\Authorization\AclBuilder',
            'bosClient'        => 'ManaPHP\Bos\Client',
            'wsPusher'         => 'ManaPHP\Ws\Pusher',
            'identity'         => 'ManaPHP\Identity',
            'coroutineManager' => 'ManaPHP\Coroutine\Manager',
            'jwt'              => 'ManaPHP\Token\Jwt',
            'scopedJwt'        => 'ManaPHP\Token\ScopedJwt',
            'wsClient'         => 'ManaPHP\Ws\Client',
            'pubSub'           => 'ManaPHP\Messaging\PubSub\Adapter\Redis',
            'dataDump'         => 'ManaPHP\Debugging\DataDump',
            'cliRunner'        => 'ManaPHP\Cli\Runner',

            'backtracePlugin' => 'ManaPHP\Debugging\BacktracePlugin',
            'debuggerPlugin'  => 'ManaPHP\Debugging\DebuggerPlugin',
            'fiddlerPlugin'   => 'ManaPHP\Debugging\FiddlerPlugin',
            'tracerPlugin'    => 'ManaPHP\Debugging\TracerPlugin',
            'loggerPlugin'    => 'ManaPHP\Logging\LoggerPlugin',
        ];
    }
}
