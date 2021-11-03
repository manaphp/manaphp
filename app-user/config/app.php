<?php

return [
    'id'                 => 'user',
    'env'                => env('APP_ENV', 'prod'),
    'debug'              => env('APP_DEBUG', false),
    'version'            => '1.1.1',
    'timezone'           => 'PRC',
    'aliases'            => [
    ],
    'dependencies'       => [
        'ManaPHP\Security\CryptInterface'          => ['master_key' => env('MASTER_KEY')],
        'ManaPHP\Http\Server\Adapter\Swoole'      => [
            'port'                  => 9501,
            'worker_num'            => 2,
            'max_request'           => 1000000,
            'enable_static_handler' => env('APP_DEBUG', false)
        ],
        //        'db'                                   => env('DB_URL'),
        //        'redis'                                => env('REDIS_URL'),
        'ManaPHP\Logging\LoggerInterface'          => [
            'class' => 'ManaPHP\Logging\Logger\Adapter\File',
            'level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'            => 'ManaPHP\Mvc\Handler',
        'ManaPHP\Http\RouterInterface'             => 'App\Router',
        'ManaPHP\Bootstrappers\TracerBootstrapper' => ['tracers' => env('APP_TRACERS', [])]
    ],
    'bootstrappers'      => [
        ManaPHP\Bootstrappers\TracerBootstrapper::class,
        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
        ManaPHP\Bootstrappers\ListenerBootstrapper::class,
        ManaPHP\Bootstrappers\MiddlewareBootstrapper::class,
    ],
    'manaphp_brand_show' => true,
];