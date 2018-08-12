<?php

return [
    'env' => env('APP_ENV', 'prod'),
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'services' => [],
    'params' => [],
    'aliases' => [],
    'components' => [
        'db' => [env('DB_URL')],
        'redis' => [env('REDIS_URL')],
        'mongodb' => [env('MONGODB_URL')],
        'logger' => [
            'level' => env('LOGGER_LEVEL', 'info'),
            'appenders' => ['file' => ['file' => '@data/logger/log.log']],
        ]
    ],
];