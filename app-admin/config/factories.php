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
                'enable_static_handler' => env('APP_DEBUG', true),
                'http_compression'      => false,
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
    'ManaPHP\Db\DbInterface'       => [
        'default' => ['class' => 'ManaPHP\Db\Db', 'uri' => env('DB_URL')],
    ]
];