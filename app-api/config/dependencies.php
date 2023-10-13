<?php

return [
    'ManaPHP\Http\ServerInterface'          => '#auto',
    'ManaPHP\Redis\RedisInterface'          => ['uri' => env('REDIS_URL')],
    'Psr\Log\LoggerInterface'               => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Rest\Handler',
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
    'ManaPHP\Http\RouterInterface'          => 'App\Router',
    'ManaPHP\Security\CryptInterface'       => ['master_key' => 'dev'],
    'ManaPHP\Eventing\TracerInterface'      => ['verbosity' => \ManaPHP\Eventing\Attribute\Verbosity::MEDIUM],
];