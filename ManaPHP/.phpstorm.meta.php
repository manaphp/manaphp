<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {

    exitPoint(\abort());

    override(
        \ManaPHP\Di\ContainerInterface::getShared(), map(
            [
                'eventManager'    => \ManaPHP\Event\ManagerInterface::class,
                'aopManager'      => \ManaPHP\Aop\ManagerInterface::class,
                'alias'           => \ManaPHP\AliasInterface::class,
                'dotenv'          => \ManaPHP\Configuration\DotenvInterface::class,
                'configure'       => \ManaPHP\Configuration\Configure::class,
                'settings'        => \ManaPHP\Configuration\SettingsInterface::class,
                'errorHandler'    => \ManaPHP\ErrorHandlerInterface::class,
                'router'          => \ManaPHP\Http\RouterInterface::class,
                'dispatcher'      => \ManaPHP\Http\DispatcherInterface::class,
                'url'             => \ManaPHP\Http\UrlInterface::class,
                'modelMetadata'   => \ManaPHP\Mvc\Model\MetadataInterface::class,
                'validator'       => \ManaPHP\Validating\ValidatorInterface::class,
                'response'        => \ManaPHP\Http\ResponseInterface::class,
                'cookies'         => \ManaPHP\Http\CookiesInterface::class,
                'request'         => \ManaPHP\Http\RequestInterface::class,
                'crypt'           => \ManaPHP\Security\CryptInterface::class,
                'flash'           => \ManaPHP\Mvc\View\FlashInterface::class,
                'flashSession'    => \ManaPHP\Mvc\View\FlashInterface::class,
                'session'         => \ManaPHP\Http\SessionInterface::class,
                'view'            => \ManaPHP\Mvc\ViewInterface::class,
                'logger'          => \ManaPHP\Logging\LoggerInterface::class,
                'renderer'        => \ManaPHP\Html\RendererInterface::class,
                'cache'           => \ManaPHP\Caching\CacheInterface::class,
                'httpClient'      => \ManaPHP\Http\ClientInterface::class,
                'restClient'      => \ManaPHP\Http\ClientInterface::class,
                'captcha'         => \ManaPHP\Http\CaptchaInterface::class,
                'csrfPlugin'      => \ManaPHP\Plugins\CsrfPlugin::class,
                'authorization'   => \ManaPHP\Http\AuthorizationInterface::class,
                'identity'        => \ManaPHP\Identifying\IdentityInterface::class,
                'msgQueue'        => \ManaPHP\Messaging\QueueInterface::class,
                'swordCompiler'   => \ManaPHP\Html\Renderer\Engine\Sword\Compiler::class,
                'viewsCache'      => \ManaPHP\Caching\CacheInterface::class,
                'htmlPurifier'    => \ManaPHP\Html\PurifierInterface::class,
                'db'              => \ManaPHP\Data\DbInterface::class,
                'redis'           => \Redis::class,
                'redisCache'      => \Redis::class,
                'redisDb'         => \Redis::class,
                'redisBroker'     => \Redis::class,
                'mongodb'         => \ManaPHP\Data\MongodbInterface::class,
                'translator'      => \ManaPHP\I18n\TranslatorInterface::class,
                'rabbitmq'        => \ManaPHP\AmqpInterface::class,
                'relationManager' => \ManaPHP\Data\Relation\Manager::class,
                'container'       => \ManaPHP\Di\ContainerInterface::class,
                'app'             => \ManaPHP\ApplicationInterface::class,
                'mailer'          => \ManaPHP\Mailing\MailerInterface::class,
                'httpServer'      => \ManaPHP\Swoole\Http\ServerInterface::class,
                'assetBundle'     => \ManaPHP\Html\Renderer\AssetBundleInterface::class,
                'aclbuilder'      => \ManaPHP\Http\Acl\BuilderInterface::class,
                'bosClient'       => \ManaPHP\Bos\ClientInterface::class,
                'wspClient'       => \ManaPHP\Ws\Pushing\ClientInterface::class,
                'coroutine'       => \ManaPHP\CoroutineInterface::class,
                'jwt'             => \ManaPHP\Token\JwtInterface::class,
                'scopedJwt'       => \ManaPHP\Token\ScopedJwtInterface::class,
                'pubSub'          => \ManaPHP\Messaging\PubSubInterface::class,
                ''                => '@|App\Services\@',
            ]
        )
    );
    override(
        \container(), map(
            [
                'eventManager'     => \ManaPHP\Event\ManagerInterface::class,
                'aopManager'       => \ManaPHP\Aop\ManagerInterface::class,
                'alias'            => \ManaPHP\AliasInterface::class,
                'dotenv'           => \ManaPHP\Configuration\DotenvInterface::class,
                'configure'        => \ManaPHP\Configuration\Configure::class,
                'settings'         => \ManaPHP\Configuration\SettingsInterface::class,
                'errorHandler'     => \ManaPHP\ErrorHandlerInterface::class,
                'router'           => \ManaPHP\Http\RouterInterface::class,
                'dispatcher'       => \ManaPHP\Http\DispatcherInterface::class,
                'url'              => \ManaPHP\Http\UrlInterface::class,
                'modelMetadata'    => \ManaPHP\Mvc\Model\MetadataInterface::class,
                'validator'        => \ManaPHP\Validating\ValidatorInterface::class,
                'response'         => \ManaPHP\Http\ResponseInterface::class,
                'cookies'          => \ManaPHP\Http\CookiesInterface::class,
                'request'          => \ManaPHP\Http\RequestInterface::class,
                'crypt'            => \ManaPHP\Security\CryptInterface::class,
                'flash'            => \ManaPHP\Mvc\View\FlashInterface::class,
                'flashSession'     => \ManaPHP\Mvc\View\FlashInterface::class,
                'session'          => \ManaPHP\Http\SessionInterface::class,
                'view'             => \ManaPHP\Mvc\ViewInterface::class,
                'logger'           => \ManaPHP\Logging\LoggerInterface::class,
                'renderer'         => \ManaPHP\Html\RendererInterface::class,
                'cache'            => \ManaPHP\Caching\CacheInterface::class,
                'httpClient'       => \ManaPHP\Http\ClientInterface::class,
                'restClient'       => \ManaPHP\Http\ClientInterface::class,
                'captcha'          => \ManaPHP\Http\CaptchaInterface::class,
                'csrfPlugin'       => \ManaPHP\Http\CsrfPlugin::class,
                'authorization'    => \ManaPHP\Http\AuthorizationInterface::class,
                'identity'         => \ManaPHP\Identifying\IdentityInterface::class,
                'msgQueue'         => \ManaPHP\Messaging\QueueInterface::class,
                'swordCompiler'    => \ManaPHP\Html\Renderer\Engine\Sword\Compiler::class,
                'viewsCache'       => \ManaPHP\Caching\CacheInterface::class,
                'htmlPurifier'     => \ManaPHP\Html\PurifierInterface::class,
                'db'               => \ManaPHP\Data\DbInterface::class,
                'redis'            => \Redis::class,
                'redisCache'       => \Redis::class,
                'redisDb'          => \Redis::class,
                'redisBroker'      => \Redis::class,
                'mongodb'          => \ManaPHP\Data\MongodbInterface::class,
                'translator'       => \ManaPHP\I18n\TranslatorInterface::class,
                'rabbitmq'         => \ManaPHP\AmqpInterface::class,
                'relationManager'  => \ManaPHP\Data\Relation\ManagerInterface::class,
                'container'        => \ManaPHP\Di\ContainerInterface::class,
                'app'              => \ManaPHP\ApplicationInterface::class,
                'mailer'           => \ManaPHP\Mailing\MailerInterface::class,
                'httpServer'       => \ManaPHP\Swoole\Http\ServerInterface::class,
                'assetBundle'      => \ManaPHP\Html\Renderer\AssetBundleInterface::class,
                'aclbuilder'       => \ManaPHP\Http\Acl\BuilderInterface::class,
                'bosClient'        => \ManaPHP\Bos\ClientInterface::class,
                'wspClient'        => \ManaPHP\Ws\Pushing\ClientInterface::class,
                'coroutineManager' => \ManaPHP\Coroutine\ManagerInterface::class,
                'jwt'              => \ManaPHP\Token\JwtInterface::class,
                'scopedJwt'        => \ManaPHP\Token\ScopedJwtInterface::class,
                'pubSub'           => \ManaPHP\Messaging\PubSubInterface::class,
                'dataDump'         => \ManaPHP\Debugging\DataDumpInterface::class,
                ''                 => '@|App\Services\@',
            ]
        )
    );

    override(
        \ManaPHP\Component::getShared(), map(
            [
                'eventManager'     => \ManaPHP\Event\ManagerInterface::class,
                'aopManager'       => \ManaPHP\Aop\ManagerInterface::class,
                'alias'            => \ManaPHP\AliasInterface::class,
                'dotenv'           => \ManaPHP\Configuration\DotenvInterface::class,
                'configure'        => \ManaPHP\Configuration\Configure::class,
                'settings'         => \ManaPHP\Configuration\SettingsInterface::class,
                'errorHandler'     => \ManaPHP\ErrorHandlerInterface::class,
                'router'           => \ManaPHP\Http\RouterInterface::class,
                'dispatcher'       => \ManaPHP\Http\DispatcherInterface::class,
                'url'              => \ManaPHP\Http\UrlInterface::class,
                'modelMetadata'    => \ManaPHP\Mvc\Model\MetadataInterface::class,
                'validator'        => \ManaPHP\Validating\ValidatorInterface::class,
                'response'         => \ManaPHP\Http\ResponseInterface::class,
                'cookies'          => \ManaPHP\Http\CookiesInterface::class,
                'request'          => \ManaPHP\Http\RequestInterface::class,
                'crypt'            => \ManaPHP\Security\CryptInterface::class,
                'flash'            => \ManaPHP\Mvc\View\FlashInterface::class,
                'flashSession'     => \ManaPHP\Mvc\View\FlashInterface::class,
                'session'          => \ManaPHP\Http\SessionInterface::class,
                'view'             => \ManaPHP\Mvc\ViewInterface::class,
                'logger'           => \ManaPHP\Logging\LoggerInterface::class,
                'renderer'         => \ManaPHP\Html\RendererInterface::class,
                'cache'            => \ManaPHP\Caching\CacheInterface::class,
                'httpClient'       => \ManaPHP\Http\ClientInterface::class,
                'restClient'       => \ManaPHP\Http\ClientInterface::class,
                'captcha'          => \ManaPHP\Http\CaptchaInterface::class,
                'csrfPlugin'       => \ManaPHP\Http\CsrfPlugin::class,
                'authorization'    => \ManaPHP\Http\AuthorizationInterface::class,
                'identity'         => \ManaPHP\Identifying\IdentityInterface::class,
                'msgQueue'         => \ManaPHP\Messaging\QueueInterface::class,
                'swordCompiler'    => \ManaPHP\Html\Renderer\Engine\Sword\Compiler::class,
                'viewsCache'       => \ManaPHP\Caching\CacheInterface::class,
                'htmlPurifier'     => \ManaPHP\Html\PurifierInterface::class,
                'db'               => \ManaPHP\Data\DbInterface::class,
                'redis'            => \Redis::class,
                'redisCache'       => \Redis::class,
                'redisDb'          => \Redis::class,
                'redisBroker'      => \Redis::class,
                'mongodb'          => \ManaPHP\Data\MongodbInterface::class,
                'translator'       => \ManaPHP\I18n\TranslatorInterface::class,
                'rabbitmq'         => \ManaPHP\AmqpInterface::class,
                'relationManager'  => \ManaPHP\Data\Relation\ManagerInterface::class,
                'container'        => \ManaPHP\Di\ContainerInterface::class,
                'app'              => \ManaPHP\ApplicationInterface::class,
                'mailer'           => \ManaPHP\Mailing\MailerInterface::class,
                'httpServer'       => \ManaPHP\Swoole\Http\ServerInterface::class,
                'assetBundle'      => \ManaPHP\Html\Renderer\AssetBundleInterface::class,
                'aclbuilder'       => \ManaPHP\Http\Acl\BuilderInterface::class,
                'bosClient'        => \ManaPHP\Bos\ClientInterface::class,
                'wspClient'        => \ManaPHP\Ws\Pushing\ClientInterface::class,
                'coroutineManager' => \ManaPHP\Coroutine\ManagerInterface::class,
                'jwt'              => \ManaPHP\Token\JwtInterface::class,
                'scopedJwt'        => \ManaPHP\Token\ScopedJwtInterface::class,
                'pubSub'           => \ManaPHP\Messaging\PubSubInterface::class,
                'dataDump'         => \ManaPHP\Debugging\DataDumpInterface::class,
                ''                 => '@|App\Services\@',
            ]
        )
    );

    override(\ManaPHP\DiInterface::getNew(), map(['' => '@']));
    override(\ManaPHP\Component::getNew(), map(['' => '@']));

    registerArgumentsSet(
        'eventManager', 'request:begin', 'request:end',
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
        'chatServer:come', 'chatServer:leave', 'chatServer:pushing', 'chatServer:pushed',
        'chatClient:push',
    );
    expectedArguments(\ManaPHP\Event\ManagerInterface::attachEvent(), 0, argumentsSet('eventManager'));
    expectedArguments(\ManaPHP\Component::attachEvent(), 0, argumentsSet('eventManager'));

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
 * @var \ManaPHP\Html\RendererInterface $renderer
 */
$view = null;
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