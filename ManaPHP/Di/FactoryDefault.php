<?php

namespace ManaPHP\Di;

class FactoryDefault extends Container
{
    public function __construct()
    {
        parent::__construct();

        $this->definitions = [
            'eventManager'     => 'ManaPHP\Event\Manager',
            'aopManager'       => 'ManaPHP\Aop\Manager',
            'poolManager'      => 'ManaPHP\Pool\Manager',
            'alias'            => 'ManaPHP\Alias',
            'dotenv'           => 'ManaPHP\Configuration\Dotenv',
            'configure'        => 'ManaPHP\Configuration\Configure',
            'settings'         => 'ManaPHP\Configuration\Settings\Adapter\Redis',
            'invoker'          => 'ManaPHP\Controller\Invoker',
            'modelMetadata'    => 'ManaPHP\Data\Db\Model\Metadata\Adapter\Memory',
            'validator'        => 'ManaPHP\Validating\Validator',
            'crypt'            => 'ManaPHP\Security\Crypt',
            'logger'           => 'ManaPHP\Logging\Logger\Adapter\File',
            'renderer'         => 'ManaPHP\Html\Renderer',
            'assetBundle'      => 'ManaPHP\Html\Renderer\AssetBundle',
            'cache'            => 'ManaPHP\Caching\Cache\Adapter\Redis',
            'httpClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Curl'],
            'restClient'       => ['class' => 'ManaPHP\Http\Client', 'engine' => 'ManaPHP\Http\Client\Engine\Stream'],
            'paginator'        => 'ManaPHP\Data\Paginator',
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
            'relationManager'  => 'ManaPHP\Data\Relation\Manager',
            'mailer'           => 'ManaPHP\Mailing\Mailer\Adapter\Smtp',
            'bosClient'        => 'ManaPHP\Bos\Client',
            'wspClient'        => 'ManaPHP\Ws\Pushing\Client',
            'identity'         => 'ManaPHP\Identifying\Identity',
            'coroutineManager' => 'ManaPHP\Coroutine\Manager',
            'jwt'              => 'ManaPHP\Token\Jwt',
            'scopedJwt'        => 'ManaPHP\Token\ScopedJwt',
            'wsClient'         => 'ManaPHP\Ws\Client',
            'pubSub'           => 'ManaPHP\Messaging\PubSub\Adapter\Redis',
            'dataDump'         => 'ManaPHP\Debugging\DataDump',
            'cliRunner'        => 'ManaPHP\Cli\Runner',
            'chatClient'       => 'ManaPHP\Ws\Chatting\Client',

            'backtracePlugin' => 'ManaPHP\Debugging\BacktracePlugin',
            'debuggerPlugin'  => 'ManaPHP\Debugging\DebuggerPlugin',
            'fiddlerPlugin'   => 'ManaPHP\Debugging\FiddlerPlugin',
            'tracerPlugin'    => 'ManaPHP\Debugging\TracerPlugin',
            'loggerPlugin'    => 'ManaPHP\Logging\LoggerPlugin',

            'dbTracer'       => 'ManaPHP\Data\Db\Tracer',
            'mongodbTracer'  => 'ManaPHP\Data\Mongodb\Tracer',
            'redisTracer'    => 'ManaPHP\Data\Redis\Tracer',
            'mailerTracer'   => 'ManaPHP\Mailing\Mailer\Tracer',
            'wsClientTracer' => 'ManaPHP\Ws\Client\Tracer',
        ];
    }
}
