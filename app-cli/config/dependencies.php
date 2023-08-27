<?php

return [
    'ManaPHP\Data\DbInterface'     => ['class' => 'ManaPHP\Data\Db', env('DB_URL')],
    'ManaPHP\Redis\RedisInterface' => [env('REDIS_URL')],
    'Psr\Log\LoggerInterface'      => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                       'level' => env('LOGGER_LEVEL', 'info')],
];