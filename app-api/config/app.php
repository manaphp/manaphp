<?php
declare(strict_types=1);

return ['ManaPHP\Di\ConfigInterface' => [
    'config' => [
        'app_id'        => 'api',
        'app_env'       => env('APP_ENV', 'prod'),
        'app_debug'     => env('APP_DEBUG', false),
        'timezone'      => 'Asia/Shanghai',
        'aliases'       => [
        ],
        'bootstrappers' => [
            ManaPHP\Debugging\DebuggerBootstrapper::class,
            ManaPHP\Http\FilterBootstrapper::class,
            ManaPHP\Eventing\TracerBootstrapper::class
        ],
    ],
]];