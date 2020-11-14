<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {

    $STATIC_METHOD_TYPES = [
        \ManaPHP\DiInterface::getShared('')   => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Dispatcher,
            'url' instanceof \ManaPHP\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'renderer' instanceof \ManaPHP\RendererInterface,
            'cache' instanceof \ManaPHP\CacheInterface,
            'ipcCache' instanceof \ManaPHP\Ipc\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Plugins\CsrfPlugin,
            'authorization' instanceof \ManaPHP\AuthorizationInterface,
            'identity' instanceof \ManaPHP\IdentityInterface,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Model\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Authorization\AclBuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wsPusher' instanceof \ManaPHP\WebSocket\PusherInterface,
            'coroutine' instanceof \ManaPHP\CoroutineInterface,
            'jwt' instanceof \ManaPHP\JwtInterface,
            'pubSub' instanceof \ManaPHP\Message\PubSubInterface,
        ],
        \di('')                               => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Dispatcher,
            'url' instanceof \ManaPHP\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'renderer' instanceof \ManaPHP\RendererInterface,
            'cache' instanceof \ManaPHP\CacheInterface,
            'ipcCache' instanceof \ManaPHP\Ipc\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Plugins\CsrfPlugin,
            'authorization' instanceof \ManaPHP\AuthorizationInterface,
            'identity' instanceof \ManaPHP\IdentityInterface,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Model\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Authorization\AclBuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wsPusher' instanceof \ManaPHP\WebSocket\PusherInterface,
            'coroutineManager' instanceof \ManaPHP\Coroutine\ManagerInterface,
            'jwt' instanceof \ManaPHP\JwtInterface,
            'pubSub' instanceof \ManaPHP\Message\PubSubInterface,
            'dataDump' instanceof \ManaPHP\DataDumpInterface,
        ],
        \ManaPHP\Di\Injectable::getShared('') => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Dispatcher,
            'url' instanceof \ManaPHP\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'renderer' instanceof \ManaPHP\RendererInterface,
            'cache' instanceof \ManaPHP\CacheInterface,
            'ipcCache' instanceof \ManaPHP\Ipc\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Plugins\CsrfPlugin,
            'authorization' instanceof \ManaPHP\AuthorizationInterface,
            'identity' instanceof \ManaPHP\IdentityInterface,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Model\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Authorization\AclBuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wsPusher' instanceof \ManaPHP\WebSocket\PusherInterface,
            'coroutineManager' instanceof \ManaPHP\Coroutine\ManagerInterface,
            'jwt' instanceof \ManaPHP\JwtInterface,
            'pubSub' instanceof \ManaPHP\Message\PubSubInterface,
            'dataDump' instanceof \ManaPHP\DataDumpInterface,
        ],
        \ManaPHP\DiInterface::get('')         => [
            '' == '@',
        ],
        \ManaPHP\Component::getInstance('')   => [
            '' == '@',
        ],
        \ManaPHP\Component::getShared('')     => [
            '' == '@',
        ]
    ];

    registerArgumentsSet(
        'eventsManager', 'request:begin', 'request:end',
        'request:authorize', 'request:authenticate',
        'request:validate', 'request:ready',
        'request:invoking', 'request:invoked',
        'response:sending', 'response:sent',
        'model:creating', 'model:created', 'model:saving', 'model:saved',
        'model:updating', 'model:updated', 'model:deleting', 'model:deleted',
        'db:connect', 'db:executing', 'db:executed', 'db:querying', 'db:queried',
        'db:begin', 'db:rollback', 'db:commit',
        'mailer:sending', 'mailer:sent',
        'redis:connect', 'redis:calling', 'redis:called',
        'httpClient:requesting', 'httpClient:requested',
        'httpClient:start', 'httpClient:complete', 'httpClient:error', 'httpClient:success',
        'wsClient:open', 'wsClient:close', 'wsClient:send', 'wsClient:recv', 'wsClient:message',
        'wsServer:open', 'wsServer:close', 'wsServer:start', 'wsServer:stop',
        'view:rendering', 'view:rendered',
        'renderer:rendering', 'renderer:rendered',
    );
    expectedArguments(\ManaPHP\Event\ManagerInterface::attachEvent(), 0, argumentsSet('eventsManager'));
    expectedArguments(\ManaPHP\Component::attachEvent(), 0, argumentsSet('eventsManager'));

    expectedArguments(\ManaPHP\Http\RequestInterface::getServer(), 0, array_keys($_SERVER)[$i]);
    expectedArguments(\ManaPHP\Http\RequestInterface::hasServer(), 0, array_keys($_SERVER)[$i]);

    expectedArguments(
        \ManaPHP\Http\ResponseInterface::setJsonContent(), 0, ['code' => 0, 'message' => '', 'data' => []]
    );
    expectedReturnValues(
        \ManaPHP\Mvc\Controller::getAcl(),
        ['*'      => '@index', '*' => 'user', '*' => '*', 'list' => '@index', 'detail' => '@index',
         'create' => '@index', 'delete' => '@index', 'edit' => '@index']
    );

    registerArgumentsSet('wsPusherEndpoint', 'admin', 'user');
    expectedArguments(\ManaPHP\WebSocket\PusherInterface::pushToId(), 2, argumentsSet('wsPusherEndpoint'));
    expectedArguments(\ManaPHP\WebSocket\PusherInterface::pushToName(), 2, argumentsSet('wsPusherEndpoint'));
    expectedArguments(\ManaPHP\WebSocket\PusherInterface::pushToRole(), 2, argumentsSet('wsPusherEndpoint'));
    expectedArguments(\ManaPHP\WebSocket\PusherInterface::pushToAll(), 1, argumentsSet('wsPusherEndpoint'));
    expectedArguments(\ManaPHP\WebSocket\PusherInterface::broadcast(), 1, argumentsSet('wsPusherEndpoint'));

    registerArgumentsSet(
        'validator_rules', [
            'required',
            'default',
            'bool',
            'int',
            'float',
            'string',
            'min'       => 1,
            'max'       => 2,
            'length'    => '1-10',
            'minLength' => 1,
            'maxLength' => 1,
            'range'     => '1-3',
            'regex'     => '#^\d+$#',
            'alpha',
            'digit',
            'xdigit',
            'alnum',
            'lower',
            'upper',
            'trim',
            'email',
            'url',
            'ip',
            'date',
            'timestamp',
            'escape',
            'xss',
            'in'        => [1, 2],
            'not_in'    => [1, 2],
            'ext'       => 'pdf,doc',
            'unique',
            'exists',
            'const',
            'account',
            'mobile',
            'safe',
            'readonly'
        ]
    );
    expectedArguments(\input(), 1, argumentsSet('validator_rules'));
    expectedArguments(\ManaPHP\Validator::validateValue(), 2, argumentsSet('validator_rules'));
    expectedArguments(\ManaPHP\Validator::validateModel(), 2, argumentsSet('validator_rules'));

    expectedArguments(
        \json_stringify(), 1,
        JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT
        | JSON_FORCE_OBJECT | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR
        | JSON_UNESCAPED_LINE_TERMINATORS
    );

    function validator_rule()
    {
        return [
            'required',
            'bool',
            'int',
            'float',
            'string',
            'alpha',
            'digit',
            'xdigit',
            'alnum',
            'lower',
            'upper',
            'trim',
            'email',
            'url',
            'ip',
            'date',
            'timestamp',
            'escape',
            'xss',
            'unique',
            'exists',
            'const',
            'account',
            'mobile',
            'safe',
            'readonly',
            'default'   => '',
            'min'       => 0,
            'max'       => 1,
            'range'     => '0-1',
            'length'    => '0-1',
            'minLength' => 1,
            'maxLength' => 1,
            'regex'     => '#^\d+#',
            'in'        => '1,2',
            'not_in'    => '1,2',
            'ext'       => 'jpg,jpeg',
        ];
    }
}

/**
 * @xglobal $view ManaPHP\ViewInterface
 */
/**
 * @var \ManaPHP\ViewInterface     $view
 * @var \ManaPHP\Di                $di
 * @var \ManaPHP\RendererInterface $renderer
 */
$view = null;
$di = null;
unset($view, $renderer);

class_exists('\Elasticsearch\Client') || class_alias('\stdClass', '\Elasticsearch\Client');
