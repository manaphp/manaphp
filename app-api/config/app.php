<?php

return [
    'id'          => 'api',
    'env'         => env('APP_ENV', 'prod'),
    'debug'       => env('APP_DEBUG', false),
    'version'     => '1.1.1',
    'timezone'    => 'PRC',
    'master_key'  => env('MASTER_KEY'),
    'params'      => [],
    'aliases'     => [],
    'components'  => [
        'ManaPHP\Http\ServerInterface' => ['port' => 9501, 'worker_num' => 4, 'max_request' => 1000000],
        'ManaPHP\Logging\LoggerInterface' => ['level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'        => 'ManaPHP\Rest\Handler',
        'ManaPHP\Http\RouterInterface'         => 'App\Router',
    ],
    'services'    => [],
    'listeners'   => [],
    'plugins'     => [
        'debugger',
        //'slowlog',
        //'logger',
    ],
    'tracers'     => ['*'],
    'middlewares' => ['cors']
];