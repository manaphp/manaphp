<?php

return [
    'id' => 'admin',
    'name' => 'ManaPHP管理系统',
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params' => [],
    'aliases' => [
    ],
    'components' => [
        '!httpServer' => [
            'port' => 9501,
            'worker_num' => 1,
            'max_request' => 1000000,
            'enable_static_handler' => env('APP_DEBUG', false)
        ],
        'db' => env('DB_URL'),
        'redis' => env('REDIS_URL'),
        'logger' => ['level' => env('LOGGER_LEVEL', 'info')],
        '!session' => ['ttl' => seconds('1d')],
        'restClient' => ['proxy' => env('REST_CLIENT_PROXY', '')],
        'bosClient' => ['endpoint' => env('BOS_UPLOADER_ENDPOINT')],
    ],
    'services' => [],
    'listeners' => [],
    'plugins' => [
        'tracer',
        //'slowlog',
        //'logger',
        //'debugger',
        'adminActionLog',
    ]
];