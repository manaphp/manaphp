<?php

return [
    'id'         => 'ws',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'master_key' => env('MASTER_KEY'),
    'params'     => [],
    'aliases'    => [
    ],
    'components' => [
        'wsServer'  => ['port' => 9501],
        'db'        => [env('DB_URL')],
        'redis'     => [env('REDIS_URL')],
        'logger'    => ['level' => env('LOGGER_LEVEL', 'info')],
        'wspServer' => ['endpoint' => 'admin'],
        'wspClient' => ['endpoint' => 'admin'],
    ],
    'plugins'    => [],
];
