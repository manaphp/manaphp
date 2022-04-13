<?php

return [
    'id'            => 'api',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'version'       => '1.1.1',
    'aliases'       => [],
    'factories'     => [
        'ManaPHP\Http\ServerInterface' => [
            'auto'   => \ManaPHP\Http\Server\Detector::detect(),
            'swoole' => [
                'class'    => 'ManaPHP\Http\Server\Adapter\Swoole',
                'port'     => 9501,
                'settings' => [
                    'worker_num'            => 2,
                    'max_request'           => 1000000,
                    'enable_static_handler' => false
                ],
            ],
            'fpm'    => [
                'class' => 'ManaPHP\Http\Server\Adapter\Fpm',
            ],
            'php'    => [
                'class' => 'ManaPHP\Http\Server\Adapter\Php',
                'port'  => 9501,
            ],
        ],
        'ManaPHP\Data\DbInterface'     => [
            'default' => ['class' => 'ManaPHP\Data\Db', env('DB_URL')],
        ]
    ],
    'dependencies'  => [
        'ManaPHP\Http\ServerInterface'          => '#auto',
        'ManaPHP\Data\RedisInterface'           => [env('REDIS_URL')],
        'ManaPHP\Logging\LoggerInterface'       => ['level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Rest\Handler',
        'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
        'ManaPHP\Http\RouterInterface'          => 'App\Router',
    ],
    'bootstrappers' => [
//        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
//        ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
    ],
    'filters'       => [
        ManaPHP\Filters\EtagFilter::class,
        ManaPHP\Filters\VerbsFilter::class,
    ]
];