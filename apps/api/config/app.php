<?php

return [
    'id'         => 'api',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'version'    => '1.1.1',
    'timezone'   => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params'     => [],
    'aliases'    => [],
    'components' => [
        '!httpServer' => ['port' => 9501, 'worker_num' => 4, 'max_request' => 1000000],
        'db'          => [env('DB_URL')],
        'redis'       => [env('REDIS_URL')],
        'logger'      => ['level' => env('LOGGER_LEVEL', 'info')],
    ],
    'services'   => [],
    'listeners'  => [],
    'plugins'    => [
        'debugger',
        'cors',
        //'slowlog',
        //'logger',
    ],
    'tracers'    => []
];