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
        'ManaPHP\Data\DbInterface'         => ['class' => 'ManaPHP\Data\Db', env('DB_URL')],
        'ManaPHP\Data\RedisInterface'      => [env('REDIS_URL')],
        'ManaPHP\Data\RedisCacheInterface' => 'ManaPHP\Data\RedisInterface',
        'ManaPHP\Logging\LoggerInterface'  => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                               'level' => env('LOGGER_LEVEL', 'info')],
    ],
    'bootstrappers' => [
        \ManaPHP\Bootstrappers\TracerBootstrapper::class
    ]
];