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

    expectedArguments(\ManaPHP\Http\RequestInterface::server(), 0, array_keys($_SERVER)[$i]);

    expectedArguments(
        \ManaPHP\Http\ResponseInterface::json(), 0, ['code' => 0, 'msg' => '', 'data' => []]
    );

    registerArgumentsSet('wspClientEndpoint', 'admin', 'user');
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToId(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToName(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToRole(), 2, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::pushToAll(), 1, argumentsSet('wspClientEndpoint'));
    expectedArguments(\ManaPHP\Ws\Pushing\ClientInterface::broadcast(), 1, argumentsSet('wspClientEndpoint'));

    registerArgumentsSet(
        'validator_constraint_type', ['bool', 'bit', 'int', 'float', 'array', 'mixed', 'iterable', 'mixed']
    );
    expectedArguments(
        \ManaPHP\Validating\Constraint\Attribute\Type::__construct(), 0, argumentsSet('validator_constraint_type')
    );

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

    registerArgumentsSet('manaphp_config', 'id', 'name', 'env', 'debug', 'params');
    expectedArguments(\ManaPHP\Di\ConfigInterface::get(), 0, argumentsSet('manaphp_config'));
    expectedArguments(\ManaPHP\Di\ConfigInterface::has(), 0, argumentsSet('manaphp_config'));
    expectedArguments(\ManaPHP\Di\ConfigInterface::set(), 0, argumentsSet('manaphp_config'));

    registerArgumentsSet(
        'filter_http_cache',
        ["etag",
         "max-age"       => 1,
         "Cache-Control" => "private, max-age=0, no-store, no-cache,must-revalidate"]
    );
    expectedArguments(
        \ManaPHP\Http\Controller\Attribute\HttpCache::__construct(), 0,
        argumentsSet('filter_http_cache')
    );
    registerArgumentsSet('logger_category', ['category' => '']);
    expectedArguments(\Psr\Log\LoggerInterface::log(), 2, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::debug(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::info(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::notice(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::warning(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::error(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::critical(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::alert(), 1, argumentsSet('logger_category'));
    expectedArguments(\Psr\Log\LoggerInterface::emergency(), 1, argumentsSet('logger_category'));

    registerArgumentsSet('request_header', [
        "accept-charset",
        "accept-encoding",
        "accept-language",
        "authorization",
        "cache-control",
        "connection",
        "content-length",
        "cookie",
        "host",
        "origin",
        "referer",
        "set-cookie",
        "transfer-encoding",
        "user-agent",
        "if-none-match",
        'x-real-ip',
        'x-forwarded-for',
        'x-requested-with',
    ]);
    expectedArguments(\ManaPHP\Http\RequestInterface::header(), 0, argumentsSet('request_header'));
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