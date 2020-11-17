<?php

return [
    'id'         => 'tcp',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'version'    => '1.1.1',
    'timezone'   => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params'     => [],
    'aliases'    => [
    ],
    'components' => [
        '!socketServer' => ['open_length_check' => true, 'package_length_type' => "L", 'package_body_offset' => 4],
        'db'            => env('DB_URL'),
        'redis'         => env('REDIS_URL'),
        'logger'        => ['level' => env('LOGGER_LEVEL', 'info')]
    ],
    'services'   => [
        'timeService' => ['endpoint' => 'http://localhost:85/time']
    ],
    'plugins'    => []
];