<?php

return [
    'id'            => 'api',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'version'       => '1.1.1',
    'timezone'      => 'PRC',
    'aliases'       => [],
    'factories'     => [
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
    'dependencies'  => [
        //'ManaPHP\Http\ServerInterface'          => '#swoole',
        'ManaPHP\Logging\LoggerInterface'       => ['level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Rest\Handler',
        'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
        'ManaPHP\Http\RouterInterface'          => 'App\Router',
    ],
    'bootstrappers' => [
        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
        ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
    ],
    'filters'       => [
        ManaPHP\Filters\EtagFilter::class,
        ManaPHP\Filters\VerbsFilter::class,
    ]
];