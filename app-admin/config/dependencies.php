<?php
declare(strict_types=1);

return [
    'ManaPHP\Security\CryptInterface'       => ['master_key' => env('MASTER_KEY', 'dev')],
    'Psr\Log\LoggerInterface'               => ['class' => 'ManaPHP\Logging\Logger\Adapter\File',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Http\SessionInterface'         => ['class'  => 'ManaPHP\Http\Session\Adapter\Redis',
                                                'ttl'    => seconds('1d'),
                                                'params' => ['path' => '/']],
    'ManaPHP\Bos\ClientInterface'           => ['endpoint' => env('BOS_UPLOADER_ENDPOINT')],
    'ManaPHP\Http\RouterInterface'          => ['class'  => 'App\Router',
                                                'prefix' => '',
    ],
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Session',
    'ManaPHP\Mailing\MailerInterface'       => 'ManaPHP\Mailing\Mailer\Adapter\File',
    'ManaPHP\Eventing\TracerInterface'      => ['verbosity' => \ManaPHP\Eventing\Attribute\Verbosity::MEDIUM],
];