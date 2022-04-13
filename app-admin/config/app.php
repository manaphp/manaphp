<?php

return [
    'id'            => 'admin',
    'name'          => 'ManaPHP管理系统',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'version'       => '1.1.1',
    'aliases'       => [
        '@web' => ''
    ],
    'factories'     => [
        'ManaPHP\Http\ServerInterface' => [
            'auto'   => \ManaPHP\Http\Server\Detector::detect(),
            'swoole' => [
                'class'    => 'ManaPHP\Http\Server\Adapter\Swoole',
                'port'     => 9501,
                'settings' => [
                    'worker_num'            => 4,
                    'max_request'           => 1000000,
                    'enable_static_handler' => env('APP_DEBUG', true)
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
        'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Mvc\Handler',
        'ManaPHP\Data\RedisInterface'           => [env('REDIS_URL')],
        'ManaPHP\Logging\LoggerInterface'       => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                    'level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\SessionInterface'         => ['class' => 'ManaPHP\Http\Session\Adapter\Redis',
                                                    'ttl'   => seconds('1d')],
        'ManaPHP\Bos\ClientInterface'           => ['endpoint' => env('BOS_UPLOADER_ENDPOINT')],
        'ManaPHP\Http\RouterInterface'          => 'App\Router',
        'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Session',
    ],
    'bootstrappers' => [
        ManaPHP\Bootstrappers\ListenerBootstrapper::class,
        ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
    ],
    'filters'       => [
        ManaPHP\Filters\AuthorizationFilter::class
    ],
];