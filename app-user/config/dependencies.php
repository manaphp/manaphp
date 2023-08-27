<?php

return [
    'ManaPHP\Http\ServerInterface'    => '#auto',
    'ManaPHP\Http\HandlerInterface'   => 'ManaPHP\Mvc\Handler',
    'ManaPHP\Redis\RedisInterface'    => [env('REDIS_URL')],
    'ManaPHP\Security\CryptInterface' => ['master_key' => env('MASTER_KEY')],
    'Psr\Log\LoggerInterface'         => [
        'class' => 'ManaPHP\Logging\Logger\Adapter\File',
        'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\RouterInterface'    => 'App\Router',
];