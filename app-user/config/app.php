<?php
declare(strict_types=1);

return ['ManaPHP\Di\ConfigInterface' => [
    'config' => [
        'app_id'    => 'user',
        'app_name'  => 'ManaPHP用户系统',
        'app_env'   => env('APP_ENV', 'prod'),
        'app_debug' => env('APP_DEBUG', false),
        'timezone'      => 'Asia/Shanghai',
        'aliases'   => [
        ],
    ],
]];