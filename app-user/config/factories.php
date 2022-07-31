<?php

return [
    'ManaPHP\Http\ServerInterface' => [
        'auto'   => \ManaPHP\Http\Server\Detector::detect(),
        'swoole' => [
            'class'    => 'ManaPHP\Http\Server\Adapter\Swoole',
            'port'     => 9501,
            'settings' => [
                'worker_num'            => 4,
                'max_request'           => 1000000,
                'enable_static_handler' => env('APP_DEBUG', true)
            ],
        ],
        'fpm'    => [
            'class' => 'ManaPHP\Http\Server\Adapter\Fpm',
        ],
        'php'    => [
            'class'    => 'ManaPHP\Http\Server\Adapter\Php',
            'port'     => 9501,
            'settings' => [
                'worker_num' => 1,
            ]
        ],
    ],
    'ManaPHP\Data\DbInterface'     => [
        'default' => ['class' => 'ManaPHP\Data\Db', env('DB_URL')],
    ]
];