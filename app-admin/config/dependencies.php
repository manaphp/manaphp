<?php
declare(strict_types=1);

return [
    'ManaPHP\Http\HandlerInterface'         => 'ManaPHP\Mvc\Handler',
    'ManaPHP\Security\CryptInterface'       => ['master_key' => env('MASTER_KEY', 'dev')],
    'ManaPHP\Redis\RedisInterface'          => ['uri' => env('REDIS_URL')],
    'Psr\Log\LoggerInterface'               => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\SessionInterface'         => ['class'  => 'ManaPHP\Http\Session\Adapter\Redis',
                                                'ttl'    => seconds('1d'),
                                                'params' => ['path' => '/']],
    'ManaPHP\Bos\ClientInterface'           => ['endpoint' => env('BOS_UPLOADER_ENDPOINT')],
    'ManaPHP\Http\RouterInterface'          => 'App\Router',
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Session',
    'ManaPHP\Mailing\MailerInterface'       => 'ManaPHP\Mailing\Mailer\Adapter\File',
    'ManaPHP\Eventing\TracerInterface'      => ['verbosity' => \ManaPHP\Eventing\Attribute\Verbosity::MEDIUM],
];