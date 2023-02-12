<?php

return [
    'ManaPHP\Http\ServerInterface'          => '#auto',
    'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Mvc\Handler',
    'ManaPHP\Data\RedisInterface'           => [env('REDIS_URL')],
    'ManaPHP\Logging\LoggerInterface'       => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\SessionInterface'         => ['class' => 'ManaPHP\Http\Session\Adapter\Redis',
                                                'ttl'   => seconds('1d'),
                                                'params'=>['path'=>'/abc']],
    'ManaPHP\Bos\ClientInterface'           => ['endpoint' => env('BOS_UPLOADER_ENDPOINT')],
    'ManaPHP\Http\RouterInterface'          => 'App\Router',
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Session',
];