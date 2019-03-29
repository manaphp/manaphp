<?php

return [
    'id' => 'api',
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'compatible_globals' => false,
    'servers' => [
        'http' => ['worker_num' => 4, 'max_request' => 1000000, 'dispatch_mode' => 1]
    ],
    'params' => [],
    'aliases' => [],
    'components' => [
        'db' => [env('DB_URL')],
        'redis' => [env('REDIS_URL')],
        'mongodb' => [env('MONGODB_URL')],
        'logger' => ['level' => env('LOGGER_LEVEL', 'info')],
    ],
    'services' => [],
    'listeners' => [],
    'plugins' => []
];