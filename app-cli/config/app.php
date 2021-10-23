<?php

return [
    'id'         => 'cli',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'version'    => '1.1.1',
    'timezone'   => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params'     => [],
    'aliases'    => [
    ],
    'dependencies' => [
  //      'db'         => env('DB_URL'),
  //      'redis'      => env('REDIS_URL'),
  //      'logger'     => ['level' => env('LOGGER_LEVEL', 'info')],
  //      'amqpClient' => 'amqp://192.168.0.8/'
    ],
    'services'   => [
        'timeService' => ['endpoint' => 'http://localhost:85/time']
    ],
    'plugins'    => [],
    'tracers'    => ['*'],
];