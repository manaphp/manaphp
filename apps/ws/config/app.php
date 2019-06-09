<?php

return [
    'debug' => env('APP_DEBUG', false),
    'version' => '1.1.1',
    'timezone' => 'PRC',
    'master_key' => '',
    'params' => ['manaphp_brand_show' => 1],
    'aliases' => [
        '@xxx' => '@root/xvdfd'
    ],
    'components' => [
        'wsServer' => ['worker_num' => 2],
        'db' => 'mysql://root:123456@localhost/manaphp?charset=utf8',
        'redis' => 'redis://localhost:6379/1?timeout=2&retry_interval=0&auth=&persistent=0',
        'mongodb' => 'mongodb://127.0.0.1/manaphp_unit_test',
        'logger' => ['level' => 'debug'],
    ],
    'services' => [],
    'listeners' => [],
    'plugins' => [
        //  'debugger'
    ]
];