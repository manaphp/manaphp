<?php

return [
    'id' => 'admin',
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params' => ['manaphp_brand_show' => 1],
    'aliases' => [
        '@messages' => '@app/Messages'
    ],
    'servers' => [
        'http' => ['worker_num' => 1, 'max_request' => 1000000, 'dispatch_mode' => 1, 'enable_static_handler' => env('APP_DEBUG', false)]
    ],
    'components' => [
        'db' => env('DB_URL'),
        'redis' => env('REDIS_URL'),
        'mongodb' => env('MONGODB_URL'),
        'logger' => ['level' => env('LOGGER_LEVEL', 'info')],
        'authorization' => \App\Areas\Rbac\Components\Rbac::class
    ],
    'services' => [],
    'listeners' => [],
    'plugins' => [
        //'debugger',
        //'fiddler',
        'adminActionLog']
];