<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {

    $STATIC_METHOD_TYPES = [
        \ManaPHP\DiInterface::getShared('')   => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\Configuration\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\Http\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Http\DispatcherInterface,
            'url' instanceof \ManaPHP\Http\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\Validating\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\Mvc\ViewInterface,
            'logger' instanceof \ManaPHP\Logging\LoggerInterface,
            'renderer' instanceof \ManaPHP\Html\RendererInterface,
            'cache' instanceof \ManaPHP\Caching\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Http\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Plugins\CsrfPlugin,
            'authorization' instanceof \ManaPHP\Http\AuthorizationInterface,
            'identity' instanceof \ManaPHP\Identifying\IdentityInterface,
            'msgQueue' instanceof \ManaPHP\Messaging\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Html\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\Caching\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Html\PurifierInterface,
            'db' instanceof \ManaPHP\Data\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\Data\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Data\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\Mailing\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Html\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Http\Acl\BuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wspClient' instanceof \ManaPHP\Ws\Pushing\ClientInterface,
            'coroutine' instanceof \ManaPHP\CoroutineInterface,
            'jwt' instanceof \ManaPHP\Token\JwtInterface,
            'scopedJwt' instanceof \ManaPHP\Token\ScopedJwtInterface,
            'pubSub' instanceof \ManaPHP\Messaging\PubSubInterface,
        ],
        \di('')                               => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\Configuration\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\Http\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Http\DispatcherInterface,
            'url' instanceof \ManaPHP\Http\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\Validating\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\Mvc\ViewInterface,
            'logger' instanceof \ManaPHP\Logging\LoggerInterface,
            'renderer' instanceof \ManaPHP\Html\RendererInterface,
            'cache' instanceof \ManaPHP\Caching\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Http\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Http\CsrfPlugin,
            'authorization' instanceof \ManaPHP\Http\AuthorizationInterface,
            'identity' instanceof \ManaPHP\Identifying\IdentityInterface,
            'msgQueue' instanceof \ManaPHP\Messaging\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Html\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\Caching\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Html\PurifierInterface,
            'db' instanceof \ManaPHP\Data\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\Data\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Data\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\Mailing\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Html\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Http\Acl\BuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wspClient' instanceof \ManaPHP\Ws\Pushing\ClientInterface,
            'coroutineManager' instanceof \ManaPHP\Coroutine\ManagerInterface,
            'jwt' instanceof \ManaPHP\Token\JwtInterface,
            'scopedJwt' instanceof \ManaPHP\Token\ScopedJwtInterface,
            'pubSub' instanceof \ManaPHP\Messaging\PubSubInterface,
            'dataDump' instanceof \ManaPHP\Debugging\DataDumpInterface,
        ],
        \ManaPHP\Di\Injectable::getShared('') => [
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'dotenv' instanceof \ManaPHP\Configuration\DotenvInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'errorHandler' instanceof \ManaPHP\ErrorHandlerInterface,
            'router' instanceof \ManaPHP\Http\RouterInterface,
            'dispatcher' instanceof \ManaPHP\Http\DispatcherInterface,
            'url' instanceof \ManaPHP\Http\UrlInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'validator' instanceof \ManaPHP\Validating\ValidatorInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'crypt' instanceof \ManaPHP\Security\CryptInterface,
            'flash' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\Mvc\View\FlashInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'view' instanceof \ManaPHP\Mvc\ViewInterface,
            'logger' instanceof \ManaPHP\Logging\LoggerInterface,
            'renderer' instanceof \ManaPHP\Html\RendererInterface,
            'cache' instanceof \ManaPHP\Caching\CacheInterface,
            'httpClient' instanceof \ManaPHP\Http\ClientInterface,
            'restClient' instanceof \ManaPHP\Http\ClientInterface,
            'captcha' instanceof \ManaPHP\Http\CaptchaInterface,
            'csrfPlugin' instanceof \ManaPHP\Http\CsrfPlugin,
            'authorization' instanceof \ManaPHP\Http\AuthorizationInterface,
            'identity' instanceof \ManaPHP\Identifying\IdentityInterface,
            'msgQueue' instanceof \ManaPHP\Messaging\QueueInterface,
            'swordCompiler' instanceof \ManaPHP\Html\Renderer\Engine\Sword\Compiler,
            'viewsCache' instanceof \ManaPHP\Caching\CacheInterface,
            'htmlPurifier' instanceof \ManaPHP\Html\PurifierInterface,
            'db' instanceof \ManaPHP\Data\DbInterface,
            'redis' instanceof \Redis,
            'redisCache' instanceof \Redis,
            'redisDb' instanceof \Redis,
            'redisBroker' instanceof \Redis,
            'mongodb' instanceof \ManaPHP\Data\MongodbInterface,
            'translator' instanceof \ManaPHP\I18n\TranslatorInterface,
            'rabbitmq' instanceof \ManaPHP\AmqpInterface,
            'relationsManager' instanceof \ManaPHP\Data\Relation\Manager,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'app' instanceof \ManaPHP\ApplicationInterface,
            'mailer' instanceof \ManaPHP\Mailing\MailerInterface,
            'httpServer' instanceof \ManaPHP\Swoole\Http\ServerInterface,
            'assetBundle' instanceof \ManaPHP\Html\Renderer\AssetBundleInterface,
            'aclbuilder' instanceof \ManaPHP\Http\Acl\BuilderInterface,
            'bosClient' instanceof \ManaPHP\Bos\ClientInterface,
            'wspClient' instanceof \ManaPHP\Ws\Pushing\ClientInterface,
            'coroutineManager' instanceof \ManaPHP\Coroutine\ManagerInterface,
            'jwt' instanceof \ManaPHP\Token\JwtInterface,
            'scopedJwt' instanceof \ManaPHP\Token\ScopedJwtInterface,
            'pubSub' instanceof \ManaPHP\Messaging\PubSubInterface,
            'dataDump' instanceof \ManaPHP\Debugging\DataDumpInterface,
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
        'response:stringify', 'response:sending', 'response:sent',
        'model:creating', 'model:created', 'model:saving', 'model:saved',
        'model:updating', 'model:updated', 'model:deleting', 'model:deleted',
        'db:connecting', 'db:connected', 'db:executing', 'db:executed', 'db:querying', 'db:queried', 'db:close',
        'db:begin', 'db:rollback', 'db:commit',
        'mailer:sending', 'mailer:sent',
        'redis:connecting', 'redis:connected', 'redis:calling', 'redis:called', 'redis:close',
        'httpClient:requesting', 'httpClient:requested',
        'httpClient:start', 'httpClient:complete', 'httpClient:error', 'httpClient:success',
        'wsClient:open', 'wsClient:close', 'wsClient:send', 'wsClient:recv', 'wsClient:message',
        'wsServer:open', 'wsServer:close', 'wsServer:start', 'wsServer:stop',
        'view:rendering', 'view:rendered',
        'renderer:rendering', 'renderer:rendered',
        'poolManager:push', 'poolManager:popping', 'poolManager:popped',
        'cache:miss', 'cache:hit',
        'wspServer:pushing', 'wspServer:pushed',
        'wspClient:push',
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

    registerArgumentsSet('wspClientEndpoint', 'admin', 'user');
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToId(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToName(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToRole(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToAll(), 1, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::broadcast(), 1, argumentsSet('wspClientEndpoint'));

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
    expectedArguments(\ManaPHP\Validating\Validator::validateValue(), 2, argumentsSet('validator_rules'));
    expectedArguments(\ManaPHP\Validating\Validator::validateModel(), 2, argumentsSet('validator_rules'));

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
 * @xglobal $view ManaPHP\Mvc\ViewInterface
 */
/**
 * @var \ManaPHP\Mvc\ViewInterface      $view
 * @var \ManaPHP\Di                     $di
 * @var \ManaPHP\Html\RendererInterface $renderer
 */
$view = null;
$di = null;
unset($view, $renderer);

class_exists('\Elasticsearch\Client') || class_alias('\stdClass', '\Elasticsearch\Client');

function model_fields($model)
{
    return array_keys(get_object_vars($model));
}

function model_field($model)
{
    return key(get_object_vars($model));
}

function model_var($model)
{
    return get_object_vars($model);
}