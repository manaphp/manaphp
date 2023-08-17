<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {
    exitPoint(\abort());

    override(\container(), map(['' => '@']));
    override(\ManaPHP\Helper\Container::get(), map(['' => '@']));
    override(\Psr\Container\ContainerInterface::get(), map(['' => '@']));

    override(\make(), map(['' => '@']));
    override(\ManaPHP\Helper\Container::make(), map(['' => '@']));
    override(\ManaPHP\Di\MakerInterface::make(), map(['' => '@']));

    registerArgumentsSet(
        'eventManager',
        'httpServer:start',
        'httpServer:masterStart', 'httpServer:managerStart', 'httpServer:workerStart',
        'request:begin', 'request:end',
        'request:authenticating', 'request:authenticated',
        'request:authorizing', 'request:authorized',
        'request:validating', 'request:validated',
        'request:ready',
        'request:invoking', 'request:invoked',
        'response:stringify',
        'request:responding', 'request:responded',
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
        'cli:invoking', 'cli:invoked',
    );
    expectedArguments(\ManaPHP\Event\EventManagerInterface::attachEvent(), 0, argumentsSet('eventManager'));
    expectedArguments(\ManaPHP\Event\EventTrait::attachEvent(), 0, argumentsSet('eventManager'));

    expectedArguments(\ManaPHP\Http\RequestInterface::getServer(), 0, array_keys($_SERVER)[$i]);
    expectedArguments(\ManaPHP\Http\RequestInterface::hasServer(), 0, array_keys($_SERVER)[$i]);

    expectedArguments(
        \ManaPHP\Http\ResponseInterface::setJsonContent(), 0, ['code' => 0, 'message' => '', 'data' => []]
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

    registerArgumentsSet('amqp_exchange_type', 'direct', 'topic', 'fanout', 'headers');
    expectedArguments(\ManaPHP\Amqp\Exchange::__construct(), 1, argumentsSet('amqp_exchange_type'));

    registerArgumentsSet(
        'amqp_exchange_features',
        ['passive'     => false, 'durable' => true,
         'auto_delete' => false, 'internal' => false,
         'nowait'      => false, 'arguments' => []]
    );
    expectedArguments(\ManaPHP\Amqp\Exchange::__construct(), 2, argumentsSet('amqp_exchange_features'));

    registerArgumentsSet(
        'amqp_queue_features', [
            'passive'     => false,
            'durable'     => true,
            'exclusive'   => false,
            'auto_delete' => false,
            'nowait'      => false,
            'arguments'   => [],
        ]
    );
    expectedArguments(\ManaPHP\Amqp\Queue::__construct(), 1, argumentsSet('amqp_queue_features'));

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

    registerArgumentsSet('manaphp_config', 'id', 'name', 'env', 'debug', 'params');
    expectedArguments(\ManaPHP\ConfigInterface::get(), 0, argumentsSet('manaphp_config'));
    expectedArguments(\ManaPHP\ConfigInterface::has(), 0, argumentsSet('manaphp_config'));
    expectedArguments(\ManaPHP\ConfigInterface::set(), 0, argumentsSet('manaphp_config'));
}

/**
 * @xglobal $view ManaPHP\Mvc\ViewInterface
 */
/**
 * @var \ManaPHP\Mvc\ViewInterface           $view
 * @var \ManaPHP\Rendering\RendererInterface $renderer
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