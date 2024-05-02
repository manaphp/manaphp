<?php
declare(strict_types=1);

return [
    'ManaPHP\Redis\RedisInterface'       => new \ManaPHP\Di\Pool([
        'default' => ['uri' => env('REDIS_URL')],
        'db'      => '#default',
        'cache'   => '#default',
        'broker'  => '#default',
    ]),
    'ManaPHP\Redis\RedisDbInterface'     => 'ManaPHP\Redis\RedisInterface#db',
    'ManaPHP\Redis\RedisBrokerInterface' => 'ManaPHP\Redis\RedisInterface#broker',
    'ManaPHP\Redis\RedisCacheInterface'  => 'ManaPHP\Redis\RedisInterface#cache',
];