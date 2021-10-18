<?php

return [
    'id'         => 'user',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'version'    => '1.1.1',
    'timezone'   => 'PRC',
    'master_key' => '',
    'params'     => ['manaphp_brand_show' => 1],
    'aliases'    => [
    ],
    'components' => [
        'ManaPHP\Http\ServerInterface'         => [
            'port'                  => 9501,
            'worker_num'            => 2,
            'max_request'           => 1000000,
            'enable_static_handler' => env('APP_DEBUG', false)
        ],
        //        'db'                                   => env('DB_URL'),
        //        'redis'                                => env('REDIS_URL'),
        'ManaPHP\Logging\Logger\Adapter\Redis' => ['level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'        => 'ManaPHP\Mvc\Handler',
    ],
    'services'   => [],
    'plugins'    => [
        'debugger',
    ],
    'tracers'    => [],
];