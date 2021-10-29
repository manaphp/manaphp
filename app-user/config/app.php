<?php

return [
    'id'           => 'user',
    'env'          => env('APP_ENV', 'prod'),
    'debug'        => env('APP_DEBUG', false),
    'version'      => '1.1.1',
    'timezone'     => 'PRC',
    'params'       => ['manaphp_brand_show' => 1],
    'aliases'      => [
    ],
    'dependencies' => [
        'ManaPHP\Security\CryptInterface' => ['master_key' => env('MASTER_KEY')],
        'ManaPHP\Http\ServerInterface'    => [
            'port'                  => 9501,
            'worker_num'            => 2,
            'max_request'           => 1000000,
            'enable_static_handler' => env('APP_DEBUG', false)
        ],
        //        'db'                                   => env('DB_URL'),
        //        'redis'                                => env('REDIS_URL'),
        'ManaPHP\Logging\LoggerInterface' => [
            'class' => 'ManaPHP\Logging\Logger\Adapter\File',
            'level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'   => 'ManaPHP\Mvc\Handler',
        'ManaPHP\Http\RouterInterface'    => 'App\Router',
    ],
    'plugins'      => [
        'debugger',
    ],
    'tracers'      => [],
];