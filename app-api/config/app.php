<?php

return [
    'id'            => 'api',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'version'       => '1.1.1',
    'timezone'      => 'PRC',
    'aliases'       => [],
    'dependencies'  => [
        'ManaPHP\Http\Server\Adapter\Swoole'    => ['port' => 9501, 'worker_num' => 4, 'max_request' => 1000000],
        'ManaPHP\Logging\LoggerInterface'       => ['level' => env('LOGGER_LEVEL', 'info')],
        'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Rest\Handler',
        'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
        'ManaPHP\Http\RouterInterface'          => 'App\Router',
    ],
    'bootstrappers' => [
        ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
    ]
];