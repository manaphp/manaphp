<?php

return [
    'ManaPHP\Db\DbInterface'       => ['class' => 'ManaPHP\Data\Db', 'uri' => env('DB_URL')],
    'ManaPHP\Redis\RedisInterface' => ['uri' => env('REDIS_URL')],
    'Psr\Log\LoggerInterface'      => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                       'level' => env('LOGGER_LEVEL', 'info')],
];