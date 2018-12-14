<?php

return [
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params' => [],
    'aliases' => [],
    'components' => [
        'db' => [env('DB_URL')],
        'redis' => [env('REDIS_URL')],
        'mongodb' => [env('MONGODB_URL')],
        'logger' => ['level' => env('LOGGER_LEVEL', 'info')],
        '!swooleHttpServer' => ['worker_num' => 1, 'max_request' => 10000, 'dispatch_mode' => 1]
    ],
    'services' => [],
    'listeners' => [],
    'plugins' => []
];