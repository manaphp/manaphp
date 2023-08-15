<?php

return [
    'ManaPHP\Http\ServerInterface'          => '#auto',
    'ManaPHP\Redis\RedisInterface'          => ['uri' => env('REDIS_URL')],
    'ManaPHP\Logging\LoggerInterface'       => ['level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Rest\Handler',
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
    'ManaPHP\Http\RouterInterface'          => 'App\Router',
    'ManaPHP\Security\CryptInterface'       => ['master_key' => 'dev']
];