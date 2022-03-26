<?php

return [
    'id'                 => 'user',
    'env'                => env('APP_ENV', 'prod'),
    'debug'              => env('APP_DEBUG', false),
    'version'            => '1.1.1',
    'timezone'           => 'PRC',
    'aliases'            => [
    ],
    'factories'          => [
        'ManaPHP\Http\ServerInterface' => [
            'swoole' => [
                'class'                 => 'ManaPHP\Http\Server\Adapter\Swoole',
                'port'                  => 9501,
                'worker_num'            => 4,
                'max_request'           => 1000000,
                'enable_static_handler' => env('APP_DEBUG', true)
            ],
            'fpm'    => [
                'class' => 'ManaPHP\Http\Server\Adapter\Fpm',
            ],
            'php'    => [
                'class' => 'ManaPHP\Http\Server\Adapter\Php',
                'port'  => 9501,
            ],
        ]
    ],
    'dependencies'       => [
        #'ManaPHP\Http\ServerInterface'             => '#swoole',
        'ManaPHP\Http\HandlerInterface'   => 'ManaPHP\Mvc\Handler',
        'ManaPHP\Security\CryptInterface' => ['master_key' => env('MASTER_KEY')],
        'db'                              => env('DB_URL'),
        'redis'                           => env('REDIS_URL'),
        'ManaPHP\Logging\LoggerInterface' => [
            'class' => 'ManaPHP\Logging\Logger\Adapter\File',
            'level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\RouterInterface'    => 'App\Router',
    ],
    'bootstrappers'      => [
        ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
        ManaPHP\Bootstrappers\ListenerBootstrapper::class,
    ],
    'manaphp_brand_show' => true,
    'filters'            => [
        App\Filters\AuthorizationFilter::class,
    ]
];