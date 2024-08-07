<?php
declare(strict_types=1);

return [
    'Psr\Log\LoggerInterface'               => ['class' => 'ManaPHP\Logging\Logger',
                                                'level' => env('LOGGER_LEVEL', 'info')],
    'ManaPHP\Identifying\IdentityInterface' => 'ManaPHP\Identifying\Identity\Adapter\Jwt',
    'ManaPHP\Http\RouterInterface'          => ['class'  => 'App\Router',
                                                'prefix' => '',
    ],
    'ManaPHP\Security\CryptInterface'       => ['master_key' => 'dev'],
    'ManaPHP\Eventing\TracerInterface'      => ['verbosity' => \ManaPHP\Eventing\Attribute\Verbosity::HIGH],
];