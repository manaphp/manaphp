<?php
declare(strict_types=1);

return [
    'ManaPHP\Redis\RedisInterface'          => ['uri' => env('REDIS_URL')],
    'Psr\Log\LoggerInterface'               => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
    'ManaPHP\Http\RouterInterface'          => ['class'  => 'App\Router',
                                                'prefix' => '/api',
    ],
    'ManaPHP\Security\CryptInterface'       => ['master_key' => 'dev'],
    'ManaPHP\Eventing\TracerInterface'      => ['verbosity' => \ManaPHP\Eventing\Attribute\Verbosity::MEDIUM],
];