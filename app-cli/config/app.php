<?php

return [
    'id'            => 'cli',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'version'       => '1.1.1',
    'timezone'      => 'PRC',
    'aliases'       => [
    ],
    'dependencies'  => [
        //      'db'         => env('DB_URL'),
        //      'redis'      => env('REDIS_URL'),
        //      'logger'     => ['level' => env('LOGGER_LEVEL', 'info')],
        //      'amqpClient' => 'amqp://192.168.0.8/'
    ],
    'bootstrappers' => []
];